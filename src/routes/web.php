<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;

/*
|--------------------------------------------------------------------------
| ゲスト（未ログイン）
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {

    // ユーザ登録画面
    Route::get('/register', [RegisterController::class, 'index']);

    // ログイン画面
    Route::get('/login', [LoginController::class, 'index'])->name('login');
});

/*
|--------------------------------------------------------------------------
| ログイン済み（認証誘導画面はメール未認証で見ることができるようにする）
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // メール認証誘導画面
    Route::get('/email/verify', function () {
        return view('verify_email');
    })->name('verification.notice');
});