<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>じゃんけんゲーム ✊✌️🖐️</title>

    {{-- スタイリング: Tailwind CSS CDN を利用してモダンなレスポンシブデザインを構築 --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- フォント: 丸みを帯びたポップな日本語フォント「M PLUS Rounded 1c」を適用 --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'M PLUS Rounded 1c', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-amber-50 via-orange-50 to-pink-50 min-h-screen text-slate-800 flex flex-col items-center justify-center p-3 sm:p-6">

    {{-- メインコンテナカード --}}
    <div class="w-full max-w-2xl bg-white/95 backdrop-blur rounded-3xl shadow-xl p-5 sm:p-8 border border-orange-100 relative">

        {{-- ---------------------------------------------------------------------- --}}
        {{-- 音声設定トグル（画面右上）: Web Speech APIの有効/無効を切り替え         --}}
        {{-- ---------------------------------------------------------------------- --}}
        <div class="flex justify-end mb-2">
            <button id="voice-toggle-btn" type="button" onclick="toggleVoiceSetting()"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-orange-100 text-orange-700 hover:bg-orange-200 border border-orange-300 transition-all shadow-sm">
                <span id="voice-icon">🔊</span>
                <span id="voice-status-text">音声: ON</span>
            </button>
        </div>

        {{-- ---------------------------------------------------------------------- --}}
        {{-- ヘッダーエリア: タイトルとサブテキスト                                   --}}
        {{-- ---------------------------------------------------------------------- --}}
        <header class="text-center mb-6">
            <span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full mb-2">TDD JANKEN GAME</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-orange-600 tracking-tight flex items-center justify-center gap-2">
                <span>じゃんけんバトル！</span>
            </h1>
            <p class="text-slate-500 text-xs sm:text-sm mt-1">「じゃんけん、ぽん！」のボイス付き！手を選んで勝負しよう！🔥</p>
        </header>

        {{-- ---------------------------------------------------------------------- --}}
        {{-- フラッシュメッセージ通知（戦績リセット時など）                         --}}
        {{-- ---------------------------------------------------------------------- --}}
        @if (session('message'))
            <div class="mb-4 p-3 bg-emerald-100 border border-emerald-300 text-emerald-800 text-sm rounded-xl text-center font-bold">
                {{ session('message') }}
            </div>
        @endif

        {{-- ---------------------------------------------------------------------- --}}
        {{-- バリデーションエラー表示（不正な手や人数が送信された場合）             --}}
        {{-- ---------------------------------------------------------------------- --}}
        @if (isset($errors) && $errors->any())
            <div class="mb-4 p-3 bg-rose-100 border border-rose-300 text-rose-800 text-sm rounded-xl text-center font-bold">
                手と人数を確認してもう一度勝負してね！
            </div>
        @endif

        {{-- ---------------------------------------------------------------------- --}}
        {{-- 戦績ステータスボード: 勝ち数・負け数・あいこ数・勝率をサマリー表示     --}}
        {{-- ---------------------------------------------------------------------- --}}
        <section class="bg-slate-50 rounded-2xl p-4 sm:p-5 border border-slate-200 mb-6">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 text-center mb-3">戦績サマリー</h2>
            <div class="grid grid-cols-4 gap-2 sm:gap-3 text-center">
                {{-- 勝ち数 --}}
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-2.5">
                    <span class="block text-xs font-semibold text-emerald-600">勝ち</span>
                    <span class="text-2xl font-black text-emerald-600">{{ $stats['wins'] }}勝</span>
                </div>
                {{-- 負け数 --}}
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-2.5">
                    <span class="block text-xs font-semibold text-rose-600">負け</span>
                    <span class="text-2xl font-black text-rose-600">{{ $stats['losses'] }}敗</span>
                </div>
                {{-- あいこ数 --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-2.5">
                    <span class="block text-xs font-semibold text-amber-600">あいこ</span>
                    <span class="text-2xl font-black text-amber-600">{{ $stats['draws'] }}分</span>
                </div>
                {{-- 勝率・総試合数 --}}
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-2.5">
                    <span class="block text-xs font-semibold text-indigo-600">勝率</span>
                    <span class="text-2xl font-black text-indigo-600">{{ $winRate }}%</span>
                    <span class="text-[10px] text-indigo-500 font-bold block">{{ $total }}戦</span>
                </div>
            </div>
        </section>

        {{-- ---------------------------------------------------------------------- --}}
        {{-- 直前の対戦結果セクション（勝負後のみ表示）                               --}}
        {{-- ---------------------------------------------------------------------- --}}
        @if ($lastGame)
            @php
                // 勝敗に応じたテーマカラー（緑: 勝ち、赤: 負け、黄: あいこ）の設定
                $resultColor = match($lastGame['result']) {
                    'win' => 'border-emerald-400 text-emerald-700 bg-emerald-50/80',
                    'lose' => 'border-rose-400 text-rose-700 bg-rose-50/80',
                    default => 'border-amber-400 text-amber-700 bg-amber-50/80',
                };
                $cpuPlayers = $lastGame['cpu_players'] ?? [];
                $gamePlayerCount = $lastGame['player_count'] ?? (count($cpuPlayers) + 1);

                // 自動音声読み上げ用テキストの生成
                $voiceSpeechText = match($lastGame['result']) {
                    'win' => 'じゃんけん、ぽん！……あなたの勝ち！やったね！',
                    'lose' => 'じゃんけん、ぽん！……あなたの負け！どんまい！',
                    default => 'じゃんけん、ぽん！……あいこでしょ！',
                };
            @endphp
            <section class="mb-6 rounded-3xl border-2 p-4 sm:p-6 text-center transition-all animate-fade-in shadow-sm {{ $resultColor }}"
                id="result-section"
                data-voice-result="{{ $voiceSpeechText }}"
                data-result-type="{{ $lastGame['result'] }}">

                {{-- 結果ヘッダー ＆ ボイス再試聴ボタン --}}
                <div class="mb-4 flex flex-col items-center justify-center">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-block px-3 py-0.5 bg-white/80 rounded-full text-xs font-extrabold tracking-wider text-slate-600 border border-slate-200">
                            {{ $gamePlayerCount }}人対戦マッチ
                        </span>
                        <button type="button" onclick="speakText('{{ $voiceSpeechText }}', 'referee', '{{ $lastGame['result'] }}')"
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black bg-white hover:bg-orange-50 text-orange-600 border border-orange-200 shadow-sm transition-all"
                            title="もう一度音声を再生する">
                            <span>📢</span> もう一度聞く
                        </button>
                    </div>
                    <h3 class="text-3xl sm:text-4xl font-black tracking-tight">
                        {{ $service->getResultLabel($lastGame['result']) }}
                    </h3>
                </div>

                {{-- 手の対決アリーナ: あなたと相手の手を超巨大サイズで一目瞭然に表示 --}}
                <div class="bg-white/95 rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm mb-5">
                    <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 mb-3">
                        ⚡ 激突！出した手の一騎打ち ⚡
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-6">
                        {{-- あなたが出した手（特大アイコン: text-6xl〜7xl） --}}
                        <div class="flex flex-col items-center justify-center p-3 sm:p-4 rounded-2xl bg-gradient-to-b from-orange-50 to-amber-50 border-2 border-orange-400 shadow-sm min-w-[130px] w-full sm:w-auto">
                            <span class="text-xs font-black text-orange-600 bg-orange-100/80 px-2 py-0.5 rounded-full mb-1">
                                あなた
                            </span>
                            <div class="text-6xl sm:text-7xl my-1 filter drop-shadow hover:scale-105 transition-transform">
                                {{ $service->getHandEmoji($lastGame['player_hand']) }}
                            </div>
                            <span class="text-base font-black text-slate-800 bg-white px-3 py-0.5 rounded-xl border border-orange-200 shadow-sm mt-1">
                                {{ $service->getHandLabel($lastGame['player_hand']) }}
                            </span>
                        </div>

                        {{-- VS アイコンバッジ --}}
                        <div class="flex items-center justify-center">
                            <span class="text-xl sm:text-2xl font-black text-orange-500 bg-orange-100 px-3.5 py-1.5 rounded-full shadow-inner border border-orange-200">
                                VS
                            </span>
                        </div>

                        {{-- 相手（全CPU）が出した手（特大アイコン: text-6xl） --}}
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            @foreach ($cpuPlayers as $cpu)
                                @php
                                    $handBoxBorder = match($cpu['result']) {
                                        'win' => 'border-emerald-400 bg-emerald-50/70',
                                        'lose' => 'border-rose-300 bg-rose-50/50',
                                        default => 'border-amber-300 bg-amber-50/50',
                                    };
                                    $cpuBadgeColor = match($cpu['result']) {
                                        'win' => 'bg-emerald-500 text-white',
                                        'lose' => 'bg-rose-500 text-white',
                                        default => 'bg-amber-500 text-white',
                                    };
                                    $cpuBadgeText = match($cpu['result']) {
                                        'win' => '勝ち',
                                        'lose' => '負け',
                                        default => 'あいこ',
                                    };
                                @endphp
                                <div class="flex flex-col items-center justify-center p-3 rounded-2xl border-2 {{ $handBoxBorder }} shadow-sm min-w-[110px]">
                                    <div class="flex items-center gap-1 mb-1">
                                        <span class="text-lg">{{ $cpu['avatar'] }}</span>
                                        <span class="text-xs font-black text-slate-700">{{ $cpu['name'] }}</span>
                                    </div>
                                    <div class="text-6xl my-1 filter drop-shadow hover:scale-105 transition-transform">
                                        {{ $service->getHandEmoji($cpu['hand']) }}
                                    </div>
                                    <div class="flex items-center gap-1 mt-1">
                                        <span class="text-sm font-black text-slate-800 bg-white px-2.5 py-0.5 rounded-xl border border-slate-200 shadow-sm">
                                            {{ $service->getHandLabel($cpu['hand']) }}
                                        </span>
                                        <span class="text-[10px] font-black px-1.5 py-0.5 rounded-lg {{ $cpuBadgeColor }}">
                                            {{ $cpuBadgeText }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- CPUたちの表情＆セリフリアクションカード一覧 --}}
                <div class="text-xs font-black text-slate-500 mb-2.5 uppercase tracking-wider text-left flex items-center gap-1.5">
                    <span>💬 対戦相手のリアクション</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                    @foreach ($cpuPlayers as $cpu)
                        <div class="bg-white/95 rounded-2xl p-3.5 shadow-sm border border-slate-200 flex flex-col justify-between">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl" title="{{ $cpu['title'] }}">{{ $cpu['avatar'] }}</span>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-black text-slate-800">{{ $cpu['name'] }}</span>
                                            <span class="text-xl" title="表情">{{ $cpu['face'] }}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400 block">{{ $cpu['title'] }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <div class="text-2xl mr-1" title="出した手">
                                        {{ $service->getHandEmoji($cpu['hand']) }}
                                    </div>
                                    {{-- 個別ボイス再生ボタン（キャラ特有のピッチと速度で発話） --}}
                                    <button type="button"
                                        onclick="speakText('{{ addslashes($cpu['dialogue']) }}', '{{ $cpu['id'] }}', '{{ $cpu['result'] }}')"
                                        class="p-1 rounded-lg bg-orange-50 hover:bg-orange-100 text-orange-600 text-xs border border-orange-200 transition-colors"
                                        title="ボイスを再生">
                                        🔊
                                    </button>
                                </div>
                            </div>

                            {{-- 吹き出しセリフ --}}
                            <div class="relative bg-slate-50 rounded-xl p-2.5 text-xs font-bold text-slate-700 border border-slate-200">
                                <span class="italic">「{{ $cpu['dialogue'] }}」</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            {{-- ---------------------------------------------------------------------- --}}
            {{-- 初期状態のキャラクター紹介パネル（対戦開始前に表示）                 --}}
            {{-- ---------------------------------------------------------------------- --}}
            <section class="mb-6 p-4 rounded-2xl bg-orange-50/50 border border-dashed border-orange-200 text-center">
                <span class="text-xs font-bold text-orange-600 uppercase tracking-wider block mb-2">対戦できるCPUキャラクターたち</span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                    @foreach ($cpuService->getAllCharacters() as $char)
                        <div class="bg-white rounded-xl p-2.5 border border-orange-100 shadow-sm">
                            <div class="text-3xl mb-1">{{ $char['avatar'] }}</div>
                            <div class="text-xs font-black text-slate-700">{{ $char['name'] }}</div>
                            <div class="text-[10px] text-slate-400">{{ $char['title'] }}</div>
                        </div>
                    @endforeach
                </div>
                <p class="text-slate-500 text-xs mt-3">好きな人数と手を選んで、バトルをスタートしてね！</p>
            </section>
        @endif

        {{-- ---------------------------------------------------------------------- --}}
        {{-- メイン操作フォーム: 参加人数選択 ＋ 手の決定ボタン                        --}}
        {{-- ---------------------------------------------------------------------- --}}
        <form action="{{ route('janken.play') }}" method="POST" class="mb-6">
            @csrf

            {{-- 参加人数選択（2人タイマン 〜 5人対戦） --}}
            <div class="mb-5 bg-orange-50/60 p-3.5 rounded-2xl border border-orange-100">
                <label class="block text-xs font-bold text-slate-600 mb-2 text-center">
                    👥 対戦人数を選んでね（現在: <span id="player-count-label" class="text-orange-600 font-extrabold">{{ $playerCount }}人対戦</span>）
                </label>
                <div class="grid grid-cols-4 gap-2">
                    @foreach ([2 => '2人 (タイマン)', 3 => '3人対戦', 4 => '4人対戦', 5 => '5人対戦'] as $num => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="player_count" value="{{ $num }}"
                                class="peer sr-only"
                                {{ $playerCount === $num ? 'checked' : '' }}
                                onchange="document.getElementById('player-count-label').textContent = '{{ $num }}人対戦';">
                            <div class="text-center py-2 px-1 rounded-xl text-xs font-bold transition-all border border-slate-200 bg-white text-slate-600 peer-checked:bg-orange-500 peer-checked:text-white peer-checked:border-orange-500 peer-checked:shadow">
                                {{ $label }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 手の選択ボタン（グー・チョキ・パー） --}}
            <div class="grid grid-cols-3 gap-3">
                {{-- グーボタン --}}
                <button type="submit" name="hand" value="rock"
                    class="group relative flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-amber-400 hover:shadow-lg active:scale-95 transition-all">
                    <span class="text-5xl sm:text-6xl group-hover:scale-110 transition-transform">✊</span>
                    <span class="mt-2 text-sm sm:text-base font-black text-slate-700 group-hover:text-amber-600">グー</span>
                </button>
                {{-- チョキボタン --}}
                <button type="submit" name="hand" value="scissors"
                    class="group relative flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-amber-400 hover:shadow-lg active:scale-95 transition-all">
                    <span class="text-5xl sm:text-6xl group-hover:scale-110 transition-transform">✌️</span>
                    <span class="mt-2 text-sm sm:text-base font-black text-slate-700 group-hover:text-amber-600">チョキ</span>
                </button>
                {{-- パーボタン --}}
                <button type="submit" name="hand" value="paper"
                    class="group relative flex flex-col items-center justify-center p-4 sm:p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-amber-400 hover:shadow-lg active:scale-95 transition-all">
                    <span class="text-5xl sm:text-6xl group-hover:scale-110 transition-transform">🖐️</span>
                    <span class="mt-2 text-sm sm:text-base font-black text-slate-700 group-hover:text-amber-600">パー</span>
                </button>
            </div>
        </form>

        {{-- ---------------------------------------------------------------------- --}}
        {{-- フッターエリア: 通算試合数表示 ＆ 戦績リセットボタン                    --}}
        {{-- ---------------------------------------------------------------------- --}}
        <div class="text-center pt-2 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400 font-medium">通算: {{ $total }} 試合</span>
            <form action="{{ route('janken.reset') }}" method="POST" onsubmit="return confirm('戦績をリセットしてもいい？');">
                @csrf
                <button type="submit" class="text-xs text-rose-500 hover:text-rose-700 font-bold hover:underline transition-colors">
                    🔄 戦績をリセット
                </button>
            </form>
        </div>
    </div>

    {{-- ====================================================================== --}}
    {{-- Web Speech API (音声合成) & Web Audio API (効果音) スクリプト             --}}
    {{-- ====================================================================== --}}
    <script>
        // 音声機能の有効/無効フラグ（ブラウザのLocalStorageに保存・復元）
        let isVoiceEnabled = localStorage.getItem('janken_voice_enabled') !== 'false';

        /**
         * 音声ON/OFF切り替えボタンの見た目を更新します。
         */
        function updateVoiceButtonUI() {
            const icon = document.getElementById('voice-icon');
            const text = document.getElementById('voice-status-text');
            const btn = document.getElementById('voice-toggle-btn');
            if (isVoiceEnabled) {
                icon.textContent = '🔊';
                text.textContent = '音声: ON';
                btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-orange-100 text-orange-700 hover:bg-orange-200 border border-orange-300 transition-all shadow-sm';
            } else {
                icon.textContent = '🔇';
                text.textContent = '音声: OFF';
                btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-slate-100 text-slate-500 hover:bg-slate-200 border border-slate-300 transition-all shadow-sm';
            }
        }

        /**
         * 音声の有効/無効設定をトグル切り替えします。
         */
        function toggleVoiceSetting() {
            isVoiceEnabled = !isVoiceEnabled;
            localStorage.setItem('janken_voice_enabled', isVoiceEnabled ? 'true' : 'false');
            updateVoiceButtonUI();
            if (isVoiceEnabled) {
                speakText('じゃんけん、ぽん！ 音声をオンにしたよ！');
            } else {
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }
            }
        }

        /**
         * Web Audio API による効果音（SE）を生成・再生します。
         * 外部ファイル不要で、オシレーターを使って合成音を再生します。
         *
         * @param {string} type 効果音タイプ ('win' | 'lose' | 'draw')
         */
        function playSoundEffect(type) {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const now = ctx.currentTime;

                if (type === 'win') {
                    // 勝利ファンファーレ♪ (C5 -> E5 -> G5 -> C6 のアルペジオ)
                    const notes = [523.25, 659.25, 783.99, 1046.50];
                    notes.forEach((freq, i) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.frequency.setValueAtTime(freq, now + i * 0.12);
                        gain.gain.setValueAtTime(0.15, now + i * 0.12);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + i * 0.12 + 0.35);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + i * 0.12);
                        osc.stop(now + i * 0.12 + 0.35);
                    });
                } else if (type === 'lose') {
                    // 敗北の下降音↓
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.frequency.setValueAtTime(320, now);
                    osc.frequency.linearRampToValueAtTime(180, now + 0.4);
                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.4);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.4);
                } else {
                    // あいこ / 決定音
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.frequency.setValueAtTime(440, now);
                    osc.frequency.exponentialRampToValueAtTime(880, now + 0.15);
                    gain.gain.setValueAtTime(0.15, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.2);
                }
            } catch (e) {
                // AudioContext非対応環境のエラー抑制
            }
        }

        /**
         * Web Speech API を用いて日本語音声を合成・読み上げます。
         * キャラクターIDに応じて声のピッチ（高低）や再生速度を自動調節します。
         *
         * @param {string} text 喋らせるテキスト
         * @param {string} characterId キャラクターID ('referee' | 'leo' | 'luna' | 'popo' | 'kaito')
         * @param {string|null} resultType 勝敗タイプ（効果音を同時に鳴らす場合に指定）
         */
        function speakText(text, characterId = 'referee', resultType = null) {
            if (!isVoiceEnabled) return;
            if (!('speechSynthesis' in window)) return;

            // 以前の発話キューをキャンセル
            window.speechSynthesis.cancel();

            // 勝敗効果音があれば同時に鳴らす
            if (resultType) {
                playSoundEffect(resultType);
            }

            const utter = new SpeechSynthesisUtterance(text);
            utter.lang = 'ja-JP';

            // キャラクターごとの声質演出
            if (characterId === 'leo') {
                utter.pitch = 0.9;   // 熱血男子: 低めピッチ
                utter.rate = 1.1;    // やや早口
            } else if (characterId === 'luna') {
                utter.pitch = 1.4;   // ツンデレお嬢様: 高めピッチ
                utter.rate = 1.15;   // キビキビ
            } else if (characterId === 'popo') {
                utter.pitch = 1.1;   // のんびりパンダ: 柔らかいピッチ
                utter.rate = 0.85;   // ゆっくりおっとり
            } else if (characterId === 'kaito') {
                utter.pitch = 0.8;   // 冷徹な策士: 低く落ち着いたピッチ
                utter.rate = 1.0;    // 標準速度
            } else {
                // 実況・審判（デフォルト）
                utter.pitch = 1.2;
                utter.rate = 1.05;
            }

            window.speechSynthesis.speak(utter);
        }

        // DOM読み込み完了時の初期化処理
        document.addEventListener('DOMContentLoaded', () => {
            updateVoiceButtonUI();

            // 直前の対戦結果があれば自動発話を実行
            const resultSection = document.getElementById('result-section');
            if (resultSection && isVoiceEnabled) {
                const voiceText = resultSection.getAttribute('data-voice-result');
                const resultType = resultSection.getAttribute('data-result-type');
                if (voiceText) {
                    // ブラウザの描画安定を待つため少しディレイを入れて発話
                    setTimeout(() => {
                        speakText(voiceText, 'referee', resultType);
                    }, 250);
                }
            }
        });
    </script>

</body>
</html>
