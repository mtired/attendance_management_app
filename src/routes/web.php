<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;

/*
|--------------------------------------------------------------------------
| ゲスト（未ログイン）
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    // ユーザ登録画面
    Route::get('/register', [RegisterController::class, 'index']);
});