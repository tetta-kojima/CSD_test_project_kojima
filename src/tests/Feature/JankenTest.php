<?php

namespace Tests\Feature;

use App\Services\JankenService;
use Tests\TestCase;

/**
 * Class JankenTest
 *
 * じゃんけん機能全体のフィーチャーテストクラス。
 * 画面アクセス、勝負リクエストの処理（勝利・敗北・あいこ時のセッション更新）、
 * 多人数モード動作、CPUキャラクターとセリフの描画、音声機能スクリプトの出力、
 * 戦績リセット、およびバリデーションエラー処理を包括的にテストします。
 */
class JankenTest extends TestCase
{
    /**
     * /janken ページに正常にアクセスでき、初期状態の戦績（0勝0敗）が表示されることをテスト
     */
    public function test_janken_page_is_accessible(): void
    {
        $response = $this->get('/janken');

        $response->assertStatus(200);
        $response->assertSee('じゃんけん');
        $response->assertSee('0勝');
        $response->assertSee('0敗');
    }

    /**
     * ルートURL（/）へのアクセスで、じゃんけんページが正常に表示またはリダイレクトされることをテスト
     */
    public function test_root_page_redirects_to_or_displays_janken(): void
    {
        $response = $this->get('/');

        if ($response->isRedirection()) {
            $response->assertRedirect('/janken');
        } else {
            $response->assertStatus(200);
            $response->assertSee('じゃんけん');
        }
    }

    /**
     * プレイヤーが勝利した場合に勝ち数（wins）が+1加算され、直前の勝負情報がセッションに保存されることをテスト
     */
    public function test_player_can_play_and_win_increments_win_count(): void
    {
        // CPUがチョキを出し、プレイヤーが勝つシナリオをモック化
        $mockService = $this->createMock(JankenService::class);
        $mockService->method('getAllHands')->willReturn([
            JankenService::HAND_ROCK,
            JankenService::HAND_SCISSORS,
            JankenService::HAND_PAPER,
        ]);
        $mockService->method('getRandomHands')->willReturn([JankenService::HAND_SCISSORS]);
        $mockService->method('judgeMulti')->willReturn(JankenService::RESULT_WIN);
        $this->app->instance(JankenService::class, $mockService);

        $response = $this->post('/janken/play', [
            'hand' => JankenService::HAND_ROCK,
            'player_count' => 2,
        ]);

        // PRGパターンに従い、/janken へリダイレクト
        $response->assertRedirect('/janken');
        // セッションの勝ち数が 1 に更新されていること
        $response->assertSessionHas('janken_stats', [
            'wins' => 1,
            'losses' => 0,
            'draws' => 0,
        ]);
        $response->assertSessionHas('last_game');
    }

    /**
     * プレイヤーが敗北した場合に負け数（losses）が+1加算されることをテスト
     */
    public function test_player_can_play_and_lose_increments_lose_count(): void
    {
        // CPUがパーを出し、プレイヤーが負けるシナリオをモック化
        $mockService = $this->createMock(JankenService::class);
        $mockService->method('getAllHands')->willReturn([
            JankenService::HAND_ROCK,
            JankenService::HAND_SCISSORS,
            JankenService::HAND_PAPER,
        ]);
        $mockService->method('getRandomHands')->willReturn([JankenService::HAND_PAPER]);
        $mockService->method('judgeMulti')->willReturn(JankenService::RESULT_LOSE);
        $this->app->instance(JankenService::class, $mockService);

        // 既存の戦績（2勝1敗0分）を持つセッションで勝負
        $response = $this->withSession([
            'janken_stats' => [
                'wins' => 2,
                'losses' => 1,
                'draws' => 0,
            ],
        ])->post('/janken/play', [
            'hand' => JankenService::HAND_ROCK,
            'player_count' => 2,
        ]);

        $response->assertRedirect('/janken');
        // 負け数が 1 -> 2 に加算されていること
        $response->assertSessionHas('janken_stats', [
            'wins' => 2,
            'losses' => 2,
            'draws' => 0,
        ]);
    }

    /**
     * あいこの場合にあいこ数（draws）が+1加算されることをテスト
     */
    public function test_player_can_play_and_draw_increments_draw_count(): void
    {
        // CPUがグーを出し、あいこになるシナリオをモック化
        $mockService = $this->createMock(JankenService::class);
        $mockService->method('getAllHands')->willReturn([
            JankenService::HAND_ROCK,
            JankenService::HAND_SCISSORS,
            JankenService::HAND_PAPER,
        ]);
        $mockService->method('getRandomHands')->willReturn([JankenService::HAND_ROCK]);
        $mockService->method('judgeMulti')->willReturn(JankenService::RESULT_DRAW);
        $this->app->instance(JankenService::class, $mockService);

        $response = $this->post('/janken/play', [
            'hand' => JankenService::HAND_ROCK,
            'player_count' => 2,
        ]);

        $response->assertRedirect('/janken');
        // あいこ数が 1 に更新されていること
        $response->assertSessionHas('janken_stats', [
            'wins' => 0,
            'losses' => 0,
            'draws' => 1,
        ]);
    }

    /**
     * 3人対戦モードにおいて、CPU 2人分の手とキャラクター情報が正しく生成・画面描画されることをテスト
     */
    public function test_player_can_play_multiplayer_mode_with_three_players(): void
    {
        $mockService = $this->createMock(JankenService::class);
        $mockService->method('getAllHands')->willReturn([
            JankenService::HAND_ROCK,
            JankenService::HAND_SCISSORS,
            JankenService::HAND_PAPER,
        ]);
        $mockService->method('getRandomHands')->with(2)->willReturn([
            JankenService::HAND_SCISSORS,
            JankenService::HAND_SCISSORS,
        ]);
        $mockService->method('judgeMulti')->willReturn(JankenService::RESULT_WIN);
        $mockService->method('getHandEmoji')->willReturn('✌️');
        $mockService->method('getHandLabel')->willReturn('チョキ');
        $mockService->method('getResultLabel')->willReturn('勝ち！🎉');
        $this->app->instance(JankenService::class, $mockService);

        $response = $this->post('/janken/play', [
            'hand' => JankenService::HAND_ROCK,
            'player_count' => 3,
        ]);

        $response->assertRedirect('/janken');
        // last_game に多人数情報が含まれていること
        $response->assertSessionHas('last_game', function (array $lastGame) {
            return $lastGame['player_count'] === 3
                && count($lastGame['cpu_hands']) === 2
                && $lastGame['result'] === JankenService::RESULT_WIN
                && isset($lastGame['cpu_players']);
        });

        // 画面に対戦相手キャラクターの名前（レオ、ルナ）が表示されること
        $pageResponse = $this->get('/janken');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('レオ');
        $pageResponse->assertSee('ルナ');
    }

    /**
     * 対戦結果画面でCPUキャラクターの表情アイコン、セリフ、手の対決アリーナが描画されることをテスト
     */
    public function test_cpu_characters_react_with_dialogue(): void
    {
        $response = $this->withSession([
            'last_game' => [
                'player_hand' => JankenService::HAND_ROCK,
                'cpu_hands' => [JankenService::HAND_SCISSORS],
                'result' => JankenService::RESULT_WIN,
                'player_count' => 2,
                'cpu_players' => [
                    [
                        'id' => 'leo',
                        'name' => 'レオ',
                        'avatar' => '🦁',
                        'title' => '熱血ライオンボーイ',
                        'hand' => JankenService::HAND_SCISSORS,
                        'result' => JankenService::RESULT_LOSE,
                        'dialogue' => 'ぐぬぬ…次は絶対に負けねえ…！',
                        'face' => '😭',
                    ],
                ],
            ],
        ])->get('/janken');

        $response->assertStatus(200);
        $response->assertSee('レオ');
        $response->assertSee('ぐぬぬ…次は絶対に負けねえ…！');
        $response->assertSee('😭');
        $response->assertSee('激突！出した手の一騎打ち');
    }

    /**
     * 画面内にWeb Speech API音声機能用の要素（音声トグルボタン、発話スクリプトなど）が出力されていることをテスト
     */
    public function test_voice_support_elements_are_present(): void
    {
        $response = $this->get('/janken');

        $response->assertStatus(200);
        $response->assertSee('音声');
        $response->assertSee('speechSynthesis');
        $response->assertSee('じゃんけん、ぽん');
    }

    /**
     * 戦績リセット（POST /janken/reset）で勝敗数が0になり、ゲーム履歴がクリアされることをテスト
     */
    public function test_player_can_reset_stats(): void
    {
        $response = $this->withSession([
            'janken_stats' => [
                'wins' => 5,
                'losses' => 3,
                'draws' => 2,
            ],
            'last_game' => [
                'player_hand' => JankenService::HAND_ROCK,
                'cpu_hands' => [JankenService::HAND_SCISSORS],
                'result' => JankenService::RESULT_WIN,
                'player_count' => 2,
            ],
        ])->post('/janken/reset');

        $response->assertRedirect('/janken');
        // 戦績が 0 にリセットされていること
        $response->assertSessionHas('janken_stats', [
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
        ]);
        // 直前のゲーム履歴が削除されていること
        $response->assertSessionMissing('last_game');
    }

    /**
     * 不正な手（invalid_hand）を送信した場合にバリデーションエラーとなることをテスト
     */
    public function test_invalid_hand_causes_validation_error(): void
    {
        $response = $this->post('/janken/play', [
            'hand' => 'invalid_hand',
        ]);

        $response->assertSessionHasErrors('hand');
    }

    /**
     * 範囲外の対戦人数（例: 10人）を送信した場合にバリデーションエラーとなることをテスト
     */
    public function test_invalid_player_count_causes_validation_error(): void
    {
        $response = $this->post('/janken/play', [
            'hand' => JankenService::HAND_ROCK,
            'player_count' => 10,
        ]);

        $response->assertSessionHasErrors('player_count');
    }
}
