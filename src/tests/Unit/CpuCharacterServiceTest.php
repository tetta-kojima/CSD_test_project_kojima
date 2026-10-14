<?php

namespace Tests\Unit;

use App\Services\CpuCharacterService;
use App\Services\JankenService;
use PHPUnit\Framework\TestCase;

/**
 * Class CpuCharacterServiceTest
 *
 * CpuCharacterService の単体テストクラス。
 * キャラクター定義の整合性（必須キー、重複なきID）、
 * 全参加者の手に基づく個別勝敗判定ロジック、およびセリフ・表情の取得を検証します。
 */
class CpuCharacterServiceTest extends TestCase
{
    private CpuCharacterService $service;

    /**
     * 各テスト前の初期化処理
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CpuCharacterService();
    }

    /**
     * 最低4人以上のユニークなIDを持つキャラクターが定義されていることをテスト
     */
    public function test_get_characters_returns_at_least_four_unique_characters(): void
    {
        $characters = $this->service->getAllCharacters();

        // 5人対戦時（プレイヤー1人＋CPU4人）に必要な4人以上が存在することを確認
        $this->assertGreaterThanOrEqual(4, count($characters));

        // キャラクターIDが一意であること
        $ids = array_column($characters, 'id');
        $this->assertCount(count($characters), array_unique($ids));
    }

    /**
     * 各キャラクター定義に必要な属性（ID、名前、アバター、肩書、勝敗別セリフ、表情）が完備されていることをテスト
     */
    public function test_character_has_required_attributes(): void
    {
        $characters = $this->service->getAllCharacters();

        foreach ($characters as $char) {
            $this->assertArrayHasKey('id', $char);
            $this->assertArrayHasKey('name', $char);
            $this->assertArrayHasKey('avatar', $char);
            $this->assertArrayHasKey('title', $char);
            $this->assertArrayHasKey('dialogues', $char);
            $this->assertArrayHasKey('faces', $char);

            // 勝敗結果（win, lose, draw）の全パターンに対応するセリフが存在すること
            $this->assertArrayHasKey(JankenService::RESULT_WIN, $char['dialogues']);
            $this->assertArrayHasKey(JankenService::RESULT_LOSE, $char['dialogues']);
            $this->assertArrayHasKey(JankenService::RESULT_DRAW, $char['dialogues']);

            // 勝敗結果（win, lose, draw）の全パターンに対応する表情が存在すること
            $this->assertArrayHasKey(JankenService::RESULT_WIN, $char['faces']);
            $this->assertArrayHasKey(JankenService::RESULT_LOSE, $char['faces']);
            $this->assertArrayHasKey(JankenService::RESULT_DRAW, $char['faces']);
        }
    }

    /**
     * judgeHandInMatch() が場に出た全手の中から、特定の手の勝敗を正確に判定することをテスト
     */
    public function test_judge_hand_in_match(): void
    {
        // ケース1: グー vs チョキ, チョキ
        // グーは勝ち(win)、チョキは負け(lose)
        $allHands = [JankenService::HAND_ROCK, JankenService::HAND_SCISSORS, JankenService::HAND_SCISSORS];
        $this->assertSame(JankenService::RESULT_WIN, $this->service->judgeHandInMatch(JankenService::HAND_ROCK, $allHands));
        $this->assertSame(JankenService::RESULT_LOSE, $this->service->judgeHandInMatch(JankenService::HAND_SCISSORS, $allHands));

        // ケース2: グー, チョキ, パー（3種類出揃ったため全員あいこ）
        $drawHands = [JankenService::HAND_ROCK, JankenService::HAND_SCISSORS, JankenService::HAND_PAPER];
        $this->assertSame(JankenService::RESULT_DRAW, $this->service->judgeHandInMatch(JankenService::HAND_ROCK, $drawHands));
        $this->assertSame(JankenService::RESULT_DRAW, $this->service->judgeHandInMatch(JankenService::HAND_SCISSORS, $drawHands));
    }

    /**
     * getReaction() がキャラクターIDと勝敗結果に応じた適切なセリフと表情を返すことをテスト
     */
    public function test_get_reaction_returns_appropriate_dialogue_and_face(): void
    {
        $characters = $this->service->getAllCharacters();
        $char = $characters[0];

        // 勝利時のセリフと表情
        $reactionWin = $this->service->getReaction($char['id'], JankenService::RESULT_WIN);
        $this->assertSame($char['dialogues'][JankenService::RESULT_WIN], $reactionWin['dialogue']);
        $this->assertSame($char['faces'][JankenService::RESULT_WIN], $reactionWin['face']);

        // 敗北時のセリフと表情
        $reactionLose = $this->service->getReaction($char['id'], JankenService::RESULT_LOSE);
        $this->assertSame($char['dialogues'][JankenService::RESULT_LOSE], $reactionLose['dialogue']);
        $this->assertSame($char['faces'][JankenService::RESULT_LOSE], $reactionLose['face']);
    }
}
