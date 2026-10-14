<?php

namespace App\Http\Controllers;

use App\Services\CpuCharacterService;
use App\Services\JankenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Class JankenController
 *
 * じゃんけんゲームのHTTPリクエストを処理するWebコントローラー。
 * ゲーム画面の描画、対戦実行（手と人数のバリデーション、勝敗判定、戦績のセッション更新）、
 * および戦績リセット処理を提供します。
 */
class JankenController extends Controller
{
    /**
     * コンストラクタ
     *
     * @param JankenService $jankenService じゃんけんロジック担当サービス
     * @param CpuCharacterService $cpuCharacterService CPUキャラクター・演出管理担当サービス
     */
    public function __construct(
        private readonly JankenService $jankenService,
        private readonly CpuCharacterService $cpuCharacterService
    ) {
    }

    /**
     * じゃんけんゲーム画面を表示します。
     *
     * セッションから現在の通算戦績（勝ち・負け・引き分け数）、選択中の対戦人数、
     * および直前の対戦結果データを取得してBladeビューに渡します。
     *
     * @param Request $request HTTPリクエスト
     * @return View じゃんけんゲーム画面ビュー
     */
    public function index(Request $request): View
    {
        // セッションから通算戦績を取得（初回アクセス時はすべて0）
        /** @var array{wins: int, losses: int, draws: int} $stats */
        $stats = $request->session()->get('janken_stats', [
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
        ]);

        // 総試合数と勝率（%）を算出（0除算を考慮）
        $total = $stats['wins'] + $stats['losses'] + $stats['draws'];
        $winRate = $total > 0 ? round(($stats['wins'] / $total) * 100, 1) : 0.0;

        // 選択中の対戦人数を取得（デフォルトは2人）
        /** @var int $playerCount */
        $playerCount = (int) $request->session()->get('player_count', 2);

        // 直前の対戦結果を取得（前回の勝負情報、CPUキャラクター情報を含む）
        /** @var array{
         *     player_hand: string,
         *     cpu_hands: array<int, string>,
         *     result: string,
         *     player_count: int,
         *     cpu_players?: array<int, array{
         *         id: string,
         *         name: string,
         *         avatar: string,
         *         title: string,
         *         hand: string,
         *         result: string,
         *         dialogue: string,
         *         face: string
         *     }>
         * }|null $lastGame */
        $lastGame = $request->session()->get('last_game');

        return view('janken', [
            'stats' => $stats,
            'total' => $total,
            'winRate' => $winRate,
            'playerCount' => $playerCount,
            'lastGame' => $lastGame,
            'service' => $this->jankenService,
            'cpuService' => $this->cpuCharacterService,
        ]);
    }

    /**
     * プレイヤーの手と対戦人数を受け取り、じゃんけんの勝負を実行します。
     *
     * 処理フロー:
     * 1. リクエストのバリデーション（有効な手、有効な人数範囲 2〜5人）
     * 2. CPUの手を人数分ランダム決定
     * 3. 多人数判定ロジックによりプレイヤーの勝敗を判定
     * 4. 各CPUの勝敗・表情・セリフを生成
     * 5. 通算戦績および直前の対戦結果をセッションに保存
     * 6. PRG (Post-Redirect-Get) パターンに従い、ゲーム画面へリダイレクト（二重送信防止）
     *
     * @param Request $request HTTPリクエスト
     * @return RedirectResponse ゲーム画面へのリダイレクト
     */
    public function play(Request $request): RedirectResponse
    {
        // 入力値の検証
        $validated = $request->validate([
            'hand' => ['required', 'string', Rule::in($this->jankenService->getAllHands())],
            'player_count' => ['nullable', 'integer', 'min:2', 'max:5'],
        ]);

        $playerHand = (string) $validated['hand'];

        // リクエストされた対戦人数、なければセッションの保持値（デフォルト2人）を使用
        $playerCount = isset($validated['player_count'])
            ? (int) $validated['player_count']
            : (int) $request->session()->get('player_count', 2);

        // 次回以降も選択人数を維持できるようセッションに記憶
        $request->session()->put('player_count', $playerCount);

        // CPUの人数（全体の参加人数 - プレイヤー1人）
        $cpuCount = $playerCount - 1;

        // CPUの手をランダム決定
        $cpuHands = $this->jankenService->getRandomHands($cpuCount);

        // プレイヤーの勝敗を判定 (win, lose, draw)
        $result = $this->jankenService->judgeMulti($playerHand, $cpuHands);

        // 各CPUキャラクターの情報とリアクションを生成
        $cpuPlayers = $this->cpuCharacterService->buildCpuPlayers($cpuHands, $playerHand);

        // セッションから現在の戦績を取得してカウントアップ
        /** @var array{wins: int, losses: int, draws: int} $stats */
        $stats = $request->session()->get('janken_stats', [
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
        ]);

        if ($result === JankenService::RESULT_WIN) {
            $stats['wins']++;
        } elseif ($result === JankenService::RESULT_LOSE) {
            $stats['losses']++;
        } else {
            $stats['draws']++;
        }

        // 更新した戦績と今回のゲーム結果をセッションに保存
        $request->session()->put('janken_stats', $stats);
        $request->session()->put('last_game', [
            'player_hand' => $playerHand,
            'cpu_hands' => $cpuHands,
            'result' => $result,
            'player_count' => $playerCount,
            'cpu_players' => $cpuPlayers,
        ]);

        return redirect()->route('janken.index');
    }

    /**
     * 通算戦績および直前の対戦結果を初期化（リセット）します。
     *
     * @param Request $request HTTPリクエスト
     * @return RedirectResponse リセット完了メッセージ付きでゲーム画面へリダイレクト
     */
    public function reset(Request $request): RedirectResponse
    {
        // 通算戦績をすべて0にリセット
        $request->session()->put('janken_stats', [
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
        ]);

        // 直前のゲーム履歴を削除
        $request->session()->forget('last_game');

        return redirect()->route('janken.index')->with('message', '戦績をリセットしたよ！');
    }
}
