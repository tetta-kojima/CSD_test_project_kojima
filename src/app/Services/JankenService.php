<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Class JankenService
 *
 * じゃんけんゲームのコアロジックを管理するサービスクラス。
 * 手（グー・チョキ・パー）の定義、絵文字や日本語ラベルの対応付け、
 * CPUのランダムな手の決定、および2人〜多人数での勝敗判定ロジックを提供します。
 */
class JankenService
{
    /**
     * じゃんけんの手の定数定義
     */
    public const HAND_ROCK = 'rock';         // グー
    public const HAND_SCISSORS = 'scissors'; // チョキ
    public const HAND_PAPER = 'paper';       // パー

    /**
     * 勝敗結果の定数定義
     */
    public const RESULT_WIN = 'win';   // プレイヤーの勝ち
    public const RESULT_LOSE = 'lose'; // プレイヤーの負け
    public const RESULT_DRAW = 'draw'; // あいこ（引き分け）

    /**
     * 手の識別子に対応する日本語表記マッピング
     *
     * @var array<string, string>
     */
    private const HAND_LABELS = [
        self::HAND_ROCK => 'グー',
        self::HAND_SCISSORS => 'チョキ',
        self::HAND_PAPER => 'パー',
    ];

    /**
     * 手の識別子に対応する絵文字アイコンマッピング
     *
     * @var array<string, string>
     */
    private const HAND_EMOJIS = [
        self::HAND_ROCK => '✊',
        self::HAND_SCISSORS => '✌️',
        self::HAND_PAPER => '🖐️',
    ];

    /**
     * 勝敗結果の識別子に対応する日本語メッセージマッピング
     *
     * @var array<string, string>
     */
    private const RESULT_LABELS = [
        self::RESULT_WIN => '勝ち！🎉',
        self::RESULT_LOSE => '負け…😢',
        self::RESULT_DRAW => 'あいこ！🤝',
    ];

    /**
     * ゲームで使用可能なすべての有効な手の一覧を取得します。
     *
     * @return array<int, string> 手の識別子の配列 (['rock', 'scissors', 'paper'])
     */
    public function getAllHands(): array
    {
        return [
            self::HAND_ROCK,
            self::HAND_SCISSORS,
            self::HAND_PAPER,
        ];
    }

    /**
     * 手の識別子から日本語表記（グー、チョキ、パー）を取得します。
     * 未定義の手が渡された場合は、そのままの文字列を返します。
     *
     * @param string $hand 手の識別子
     * @return string 日本語ラベル
     */
    public function getHandLabel(string $hand): string
    {
        return self::HAND_LABELS[$hand] ?? $hand;
    }

    /**
     * 手の識別子に対応する絵文字（✊、✌️、🖐️）を取得します。
     * 未定義の手が渡された場合は、代替絵文字（❓）を返します。
     *
     * @param string $hand 手の識別子
     * @return string 手の絵文字
     */
    public function getHandEmoji(string $hand): string
    {
        return self::HAND_EMOJIS[$hand] ?? '❓';
    }

    /**
     * 勝敗結果の識別子から画面表示用の日本語ラベルを取得します。
     *
     * @param string $result 結果の識別子 (win, lose, draw)
     * @return string 表示用日本語メッセージ
     */
    public function getResultLabel(string $result): string
    {
        return self::RESULT_LABELS[$result] ?? $result;
    }

    /**
     * CPUの手をランダム（等確率）に1つ選択して返します。
     *
     * @return string 手の識別子 (rock, scissors, paper のいずれか)
     */
    public function getRandomHand(): string
    {
        $hands = $this->getAllHands();
        $randomIndex = array_rand($hands);

        return $hands[$randomIndex];
    }

    /**
     * 指定された人数分のCPUの手をランダムに決定して配列で返します。
     *
     * @param int $count 生成するCPUの人数
     * @return array<int, string> CPUの手の配列
     */
    public function getRandomHands(int $count): array
    {
        $hands = [];
        for ($i = 0; $i < $count; $i++) {
            $hands[] = $this->getRandomHand();
        }

        return $hands;
    }

    /**
     * 2人対戦（プレイヤー vs CPU 1人）における勝敗を判定します。
     * 多人数判定ロジック `judgeMulti()` に委譲します。
     *
     * @param string $playerHand プレイヤーが出した手
     * @param string $cpuHand CPUが出した手
     * @return string 勝敗結果 (win, lose, draw)
     * @throws InvalidArgumentException 不正な手が渡された場合
     */
    public function judge(string $playerHand, string $cpuHand): string
    {
        return $this->judgeMulti($playerHand, [$cpuHand]);
    }

    /**
     * 多人数（2人〜5人など）でのじゃんけんの勝敗をプレイヤー目線で判定します。
     *
     * 【多人数じゃんけんのルール】
     * 1. 場に出た手の種類が1種類（全員同じ手） => あいこ (draw)
     * 2. 場に出た手の種類が3種類（グー・チョキ・パーがすべて存在） => あいこ (draw)
     * 3. 場に出た手の種類が2種類のみ => 2種類の中で勝つ手を出した参加者が勝ち (win)、負ける手を出した参加者が負け (lose)
     *
     * @param string $playerHand プレイヤーが出した手
     * @param array<int, string> $cpuHands 対戦相手（全CPU）が出した手の配列
     * @return string プレイヤーの勝敗結果 (win, lose, draw)
     * @throws InvalidArgumentException 手が不正な値、またはCPUの手が空の場合
     */
    public function judgeMulti(string $playerHand, array $cpuHands): string
    {
        $validHands = $this->getAllHands();

        // プレイヤーの手の妥当性チェック
        if (! in_array($playerHand, $validHands, true)) {
            throw new InvalidArgumentException("Invalid player hand: {$playerHand}");
        }

        // 対戦相手（CPU）が存在するかチェック
        if (empty($cpuHands)) {
            throw new InvalidArgumentException("CPU hands must not be empty");
        }

        // 各CPUの手の妥当性チェック
        foreach ($cpuHands as $hand) {
            if (! in_array($hand, $validHands, true)) {
                throw new InvalidArgumentException("Invalid cpu hand: {$hand}");
            }
        }

        // 参加者全員（プレイヤー＋全CPU）の手を統合し、場に出た手のユニーク数を算出
        $allHands = array_merge([$playerHand], $cpuHands);
        $uniqueHands = array_values(array_unique($allHands));
        $uniqueCount = count($uniqueHands);

        // 全員同じ手（1種類）または全種類出揃った（3種類）場合は「あいこ」
        if ($uniqueCount === 1 || $uniqueCount === 3) {
            return self::RESULT_DRAW;
        }

        // 場に出た手が2種類の場合：2種類のうち勝ち手を特定し、プレイヤーの手と比較
        $winningHand = $this->getWinningHandBetweenTwo($uniqueHands[0], $uniqueHands[1]);

        return ($playerHand === $winningHand) ? self::RESULT_WIN : self::RESULT_LOSE;
    }

    /**
     * 2種類の手の中で勝利する側の手を返します。
     *
     * 判定ルール:
     * - グー vs チョキ => グーの勝ち
     * - チョキ vs パー => チョキの勝ち
     * - パー vs グー => パーの勝ち
     *
     * @param string $handA 手A
     * @param string $handB 手B
     * @return string 勝つ側の手
     */
    private function getWinningHandBetweenTwo(string $handA, string $handB): string
    {
        $winMap = [
            self::HAND_ROCK => self::HAND_SCISSORS,
            self::HAND_SCISSORS => self::HAND_PAPER,
            self::HAND_PAPER => self::HAND_ROCK,
        ];

        return ($winMap[$handA] === $handB) ? $handA : $handB;
    }
}
