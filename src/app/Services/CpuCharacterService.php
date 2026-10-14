<?php

namespace App\Services;

/**
 * Class CpuCharacterService
 *
 * じゃんけん対戦相手となるCPUキャラクターの個性、アバター、表情、セリフ、
 * および各CPU目線での勝敗判定・リアクション生成を担当するサービスクラスです。
 */
class CpuCharacterService
{
    /**
     * 対戦相手として登場するCPUキャラクターの定義リスト
     *
     * 各キャラクターの構成:
     * - id: 一意識別子
     * - name: キャラクター名（レオ、ルナ、ポポ、カイト）
     * - avatar: キャラクターのアイコン絵文字
     * - title: キャラクターの肩書・二つ名
     * - personality: 性格の概要
     * - dialogues: 勝敗結果 (win, lose, draw) に応じたセリフ
     * - faces: 勝敗結果 (win, lose, draw) に応じた表情アイコン
     *
     * @var array<int, array{
     *     id: string,
     *     name: string,
     *     avatar: string,
     *     title: string,
     *     personality: string,
     *     dialogues: array<string, string>,
     *     faces: array<string, string>
     * }>
     */
    private const CHARACTERS = [
        [
            'id' => 'leo',
            'name' => 'レオ',
            'avatar' => '🦁',
            'title' => '熱血ライオンボーイ',
            'personality' => '負けず嫌いな熱血系',
            'dialogues' => [
                JankenService::RESULT_WIN => 'うっしゃぁぁ！オレの完全勝利だぜーッ！！🔥',
                JankenService::RESULT_LOSE => 'ぐぬぬ…次は絶対に負けねえ…！',
                JankenService::RESULT_DRAW => 'チッ、気が合うじゃねえか！もう一戦だ！',
            ],
            'faces' => [
                JankenService::RESULT_WIN => '😎',
                JankenService::RESULT_LOSE => '😭',
                JankenService::RESULT_DRAW => '😤',
            ],
        ],
        [
            'id' => 'luna',
            'name' => 'ルナ',
            'avatar' => '🐱',
            'title' => 'ツンデレお嬢様キャット',
            'personality' => 'プライド高めの強がり系',
            'dialogues' => [
                JankenService::RESULT_WIN => 'ふふん、私の勝ちね！当然の結果よ♪',
                JankenService::RESULT_LOSE => 'な、何よこれ！たまたま運が良かっただけなんだからねっ…！',
                JankenService::RESULT_DRAW => 'べ、別にあなたと息を合わせたわけじゃないんだから！',
            ],
            'faces' => [
                JankenService::RESULT_WIN => '😏',
                JankenService::RESULT_LOSE => '🙈',
                JankenService::RESULT_DRAW => '🙄',
            ],
        ],
        [
            'id' => 'popo',
            'name' => 'ポポ',
            'avatar' => '🐼',
            'title' => 'のんびりパンダ',
            'personality' => 'マイペースな癒やし系',
            'dialogues' => [
                JankenService::RESULT_WIN => 'えへへ〜勝っちゃったぁ〜ぽかぽか〜🍵',
                JankenService::RESULT_LOSE => 'あれれ〜？負けちゃったぁ…お昼寝するぅ〜💤',
                JankenService::RESULT_DRAW => 'わぁ〜おそろいだねぇ〜なかよし〜🍀',
            ],
            'faces' => [
                JankenService::RESULT_WIN => '🥳',
                JankenService::RESULT_LOSE => '🥺',
                JankenService::RESULT_DRAW => '☺️',
            ],
        ],
        [
            'id' => 'kaito',
            'name' => 'カイト',
            'avatar' => '🦊',
            'title' => '冷徹なデータフォックス',
            'personality' => '計算と分析の策士系',
            'dialogues' => [
                JankenService::RESULT_WIN => 'ふっ、全て計算通りです。あなたの手は読めていました',
                JankenService::RESULT_LOSE => 'バ、バカな…！私の計算が狂うなんて有り得ない…！？',
                JankenService::RESULT_DRAW => 'ほう、私の思考をトレースしてきましたか…興味深い',
            ],
            'faces' => [
                JankenService::RESULT_WIN => '🧐',
                JankenService::RESULT_LOSE => '😱',
                JankenService::RESULT_DRAW => '🤔',
            ],
        ],
    ];

    /**
     * 定義されているすべてのCPUキャラクターの一覧を取得します。
     *
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     avatar: string,
     *     title: string,
     *     personality: string,
     *     dialogues: array<string, string>,
     *     faces: array<string, string>
     * }> キャラクター配列
     */
    public function getAllCharacters(): array
    {
        return self::CHARACTERS;
    }

    /**
     * インデックス番号に対応するキャラクター情報を取得します。
     * 人数が多い場合は剰余算（%）によりキャラクターが循環して安全に取得されます。
     *
     * @param int $index CPUの連番インデックス（0始まり）
     * @return array{
     *     id: string,
     *     name: string,
     *     avatar: string,
     *     title: string,
     *     personality: string,
     *     dialogues: array<string, string>,
     *     faces: array<string, string>
     * } キャラクター情報
     */
    public function getCharacterByIndex(int $index): array
    {
        $count = count(self::CHARACTERS);
        $safeIndex = $index % $count;

        return self::CHARACTERS[$safeIndex];
    }

    /**
     * 指定したキャラクターIDと勝敗結果に基づいて、対応するセリフと表情アイコンを取得します。
     *
     * @param string $characterId キャラクターの一意識別子 (leo, luna, popo, kaito)
     * @param string $result 当該キャラクター目線での勝敗結果 (win, lose, draw)
     * @return array{dialogue: string, face: string} セリフと表情アイコンの連想配列
     */
    public function getReaction(string $characterId, string $result): array
    {
        foreach (self::CHARACTERS as $char) {
            if ($char['id'] === $characterId) {
                return [
                    'dialogue' => $char['dialogues'][$result] ?? '',
                    'face' => $char['faces'][$result] ?? '😐',
                ];
            }
        }

        return [
            'dialogue' => '……',
            'face' => '😐',
        ];
    }

    /**
     * 全参加者の手の組み合わせの中で、特定の手（CPU自身の手など）が「勝ち」「負け」「あいこ」のいずれであるかを判定します。
     *
     * @param string $hand 判定対象の手 (rock, scissors, paper)
     * @param array<int, string> $allHands 全参加者（プレイヤー＋全CPU）が出した手の配列
     * @return string 勝敗結果 (win, lose, draw)
     */
    public function judgeHandInMatch(string $hand, array $allHands): string
    {
        $uniqueHands = array_values(array_unique($allHands));
        $uniqueCount = count($uniqueHands);

        // 1種類（全員同手）または3種類（全手出揃い）の場合は引き分け
        if ($uniqueCount === 1 || $uniqueCount === 3) {
            return JankenService::RESULT_DRAW;
        }

        // 2種類の場合の勝ち手判定マップ
        $winMap = [
            JankenService::HAND_ROCK => JankenService::HAND_SCISSORS,
            JankenService::HAND_SCISSORS => JankenService::HAND_PAPER,
            JankenService::HAND_PAPER => JankenService::HAND_ROCK,
        ];

        // 2種類のうち勝利する手を決定
        $winningHand = ($winMap[$uniqueHands[0]] === $uniqueHands[1]) ? $uniqueHands[0] : $uniqueHands[1];

        return ($hand === $winningHand) ? JankenService::RESULT_WIN : JankenService::RESULT_LOSE;
    }

    /**
     * 対戦に参加した全CPUの対戦結果オブジェクト（キャラクター名、アバター、手、勝敗、セリフ、表情）を構築します。
     *
     * @param array<int, string> $cpuHands 対戦で各CPUが出した手の配列
     * @param string $playerHand プレイヤーが出した手
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     avatar: string,
     *     title: string,
     *     hand: string,
     *     result: string,
     *     dialogue: string,
     *     face: string
     * }> 画面表示用のCPUプレイヤー情報リスト
     */
    public function buildCpuPlayers(array $cpuHands, string $playerHand): array
    {
        // プレイヤーと全CPUの手を統合
        $allHands = array_merge([$playerHand], $cpuHands);
        $cpuPlayers = [];

        foreach ($cpuHands as $index => $cpuHand) {
            // インデックス順に対応するキャラクタープロフィールを割り当て
            $char = $this->getCharacterByIndex($index);

            // このCPUの手が今回の勝負で勝ちか負けかあいこかを判定
            $cpuResult = $this->judgeHandInMatch($cpuHand, $allHands);

            // CPUの勝敗に応じたセリフと表情を取得
            $reaction = $this->getReaction($char['id'], $cpuResult);

            $cpuPlayers[] = [
                'id' => $char['id'],
                'name' => $char['name'],
                'avatar' => $char['avatar'],
                'title' => $char['title'],
                'hand' => $cpuHand,
                'result' => $cpuResult,
                'dialogue' => $reaction['dialogue'],
                'face' => $reaction['face'],
            ];
        }

        return $cpuPlayers;
    }
}
