<?php

use App\Http\Controllers\JankenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Web ルーティング定義)
|--------------------------------------------------------------------------
|
| じゃんけんゲームアプリケーションのルーティングを定義します。
| トップページ（/）および /janken でゲーム画面を表示し、
| POST リクエストで勝負実行や戦績リセットを処理します。
|
*/

// トップページ（/）: じゃんけんゲーム画面を表示
Route::get('/', [JankenController::class, 'index'])->name('home');

// じゃんけんゲーム画面表示ルート
Route::get('/janken', [JankenController::class, 'index'])->name('janken.index');

// じゃんけん勝負実行ルート（手と対戦人数を受信して勝敗判定）
Route::post('/janken/play', [JankenController::class, 'play'])->name('janken.play');

// 戦績リセット実行ルート（セッションの勝敗数・履歴を初期化）
Route::post('/janken/reset', [JankenController::class, 'reset'])->name('janken.reset');
