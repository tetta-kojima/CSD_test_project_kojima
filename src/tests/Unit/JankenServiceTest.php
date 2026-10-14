<?php

namespace Tests\Unit;

use App\Services\JankenService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class JankenServiceTest
 *
 * JankenService の単体テストクラス。
 * 2人対戦・多人数対戦の勝敗判定ロジック、異常系（不正な手、空の相手配列）、
 * ランダム手生成、ラベル・絵文字取得メソッドの正常動作を検証します。
 */
class JankenServiceTest extends TestCase
{
    private JankenService $service;

    /**
     * 各テスト前の初期化処理
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JankenService();
    }

    /**
     * プレイヤーがグー（rock）を出した際の2人対戦勝敗判定をテスト
     */
    public function test_judge_rock(): void
    {
        // グー vs グー => あいこ
        $this->assertSame(JankenService::RESULT_DRAW, $this->service->judge(JankenService::HAND_ROCK, JankenService::HAND_ROCK));
        // グー vs チョキ => 勝ち
        $this->assertSame(JankenService::RESULT_WIN, $this->service->judge(JankenService::HAND_ROCK, JankenService::HAND_SCISSORS));
        // グー vs パー => 負け
        $this->assertSame(JankenService::RESULT_LOSE, $this->service->judge(JankenService::HAND_ROCK, JankenService::HAND_PAPER));
    }

    /**
     * プレイヤーがチョキ（scissors）を出した際の2人対戦勝敗判定をテスト
     */
    public function test_judge_scissors(): void
    {
        // チョキ vs グー => 負け
        $this->assertSame(JankenService::RESULT_LOSE, $this->service->judge(JankenService::HAND_SCISSORS, JankenService::HAND_ROCK));
        // チョキ vs チョキ => あいこ
        $this->assertSame(JankenService::RESULT_DRAW, $this->service->judge(JankenService::HAND_SCISSORS, JankenService::HAND_SCISSORS));
        // チョキ vs パー => 勝ち
        $this->assertSame(JankenService::RESULT_WIN, $this->service->judge(JankenService::HAND_SCISSORS, JankenService::HAND_PAPER));
    }

    /**
     * プレイヤーがパー（paper）を出した際の2人対戦勝敗判定をテスト
     */
    public function test_judge_paper(): void
    {
        // パー vs グー => 勝ち
        $this->assertSame(JankenService::RESULT_WIN, $this->service->judge(JankenService::HAND_PAPER, JankenService::HAND_ROCK));
        // パー vs チョキ => 負け
        $this->assertSame(JankenService::RESULT_LOSE, $this->service->judge(JankenService::HAND_PAPER, JankenService::HAND_SCISSORS));
        // パー vs パー => あいこ
        $this->assertSame(JankenService::RESULT_DRAW, $this->service->judge(JankenService::HAND_PAPER, JankenService::HAND_PAPER));
    }

    /**
     * 不正なプレイヤーの手が渡された場合に InvalidArgumentException がスローされることをテスト
     */
    public function test_judge_throws_exception_on_invalid_player_hand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->judge('invalid_hand', JankenService::HAND_ROCK);
    }

    /**
     * 不正なCPUの手が渡された場合に InvalidArgumentException がスローされることをテスト
     */
    public function test_judge_throws_exception_on_invalid_cpu_hand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->judge(JankenService::HAND_ROCK, 'invalid_hand');
    }

    /**
     * getRandomHand() が常に定義済みの有効な手を返すことをテスト
     */
    public function test_get_random_hand_returns_valid_hand(): void
    {
        $validHands = [JankenService::HAND_ROCK, JankenService::HAND_SCISSORS, JankenService::HAND_PAPER];
        for ($i = 0; $i < 20; $i++) {
            $hand = $this->service->getRandomHand();
            $this->assertContains($hand, $validHands);
        }
    }

    /**
     * getRandomHands() が指定された人数分の有効な手を生成することをテスト
     */
    public function test_get_random_hands_returns_expected_count(): void
    {
        $validHands = [JankenService::HAND_ROCK, JankenService::HAND_SCISSORS, JankenService::HAND_PAPER];
        $hands = $this->service->getRandomHands(4);

        $this->assertCount(4, $hands);
        foreach ($hands as $hand) {
            $this->assertContains($hand, $validHands);
        }
    }

    /**
     * 多人数対戦でプレイヤーが勝利するパターンの判定をテスト
     */
    public function test_judge_multi_win(): void
    {
        // 3人戦（プレイヤー✊、CPU ✌️, ✌️ => プレイヤー単独勝ち）
        $result = $this->service->judgeMulti(
            JankenService::HAND_ROCK,
            [JankenService::HAND_SCISSORS, JankenService::HAND_SCISSORS]
        );
        $this->assertSame(JankenService::RESULT_WIN, $result);

        // 4人戦（プレイヤー🖐️、CPU 🖐️, ✊, ✊ => プレイヤー含むパーを出した2人が勝ち）
        $result4 = $this->service->judgeMulti(
            JankenService::HAND_PAPER,
            [JankenService::HAND_PAPER, JankenService::HAND_ROCK, JankenService::HAND_ROCK]
        );
        $this->assertSame(JankenService::RESULT_WIN, $result4);
    }

    /**
     * 多人数対戦でプレイヤーが敗北するパターンの判定をテスト
     */
    public function test_judge_multi_lose(): void
    {
        // 3人戦（プレイヤー✊、CPU 🖐️, 🖐️ => パーを出したCPUの勝ち、プレイヤー負け）
        $result = $this->service->judgeMulti(
            JankenService::HAND_ROCK,
            [JankenService::HAND_PAPER, JankenService::HAND_PAPER]
        );
        $this->assertSame(JankenService::RESULT_LOSE, $result);

        // 4人戦（プレイヤー✌️、CPU ✊, ✊, ✌️ => グーを出したCPUの勝ち、プレイヤー負け）
        $result4 = $this->service->judgeMulti(
            JankenService::HAND_SCISSORS,
            [JankenService::HAND_ROCK, JankenService::HAND_ROCK, JankenService::HAND_SCISSORS]
        );
        $this->assertSame(JankenService::RESULT_LOSE, $result4);
    }

    /**
     * 多人数対戦で全員が同じ手を出した場合に「あいこ」となることをテスト
     */
    public function test_judge_multi_draw_all_same(): void
    {
        // 全員がグー
        $result = $this->service->judgeMulti(
            JankenService::HAND_ROCK,
            [JankenService::HAND_ROCK, JankenService::HAND_ROCK]
        );
        $this->assertSame(JankenService::RESULT_DRAW, $result);
    }

    /**
     * 多人数対戦で場に3種類すべての手が出揃った場合に「あいこ」となることをテスト
     */
    public function test_judge_multi_draw_all_three_hands_present(): void
    {
        // 3人戦で ✊, ✌️, 🖐️ が各1人ずつ出た場合
        $result3 = $this->service->judgeMulti(
            JankenService::HAND_ROCK,
            [JankenService::HAND_SCISSORS, JankenService::HAND_PAPER]
        );
        $this->assertSame(JankenService::RESULT_DRAW, $result3);

        // 5人戦で全種類の手が混在している場合
        $result5 = $this->service->judgeMulti(
            JankenService::HAND_SCISSORS,
            [
                JankenService::HAND_ROCK,
                JankenService::HAND_ROCK,
                JankenService::HAND_SCISSORS,
                JankenService::HAND_PAPER,
            ]
        );
        $this->assertSame(JankenService::RESULT_DRAW, $result5);
    }

    /**
     * 多人数対戦でCPU手配列が空の場合に InvalidArgumentException がスローされることをテスト
     */
    public function test_judge_multi_throws_exception_on_empty_cpu_hands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->judgeMulti(JankenService::HAND_ROCK, []);
    }

    /**
     * 多人数対戦で不正なCPUの手が含まれる場合に InvalidArgumentException がスローされることをテスト
     */
    public function test_judge_multi_throws_exception_on_invalid_hand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->judgeMulti(JankenService::HAND_ROCK, ['invalid_hand']);
    }

    /**
     * 日本語ラベル・絵文字・勝敗ラベルが空文字でなく正しく返されることをテスト
     */
    public function test_labels_and_emojis(): void
    {
        $this->assertNotEmpty($this->service->getHandLabel(JankenService::HAND_ROCK));
        $this->assertNotEmpty($this->service->getHandEmoji(JankenService::HAND_ROCK));
        $this->assertNotEmpty($this->service->getResultLabel(JankenService::RESULT_WIN));
    }
}
