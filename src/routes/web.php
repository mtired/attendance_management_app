<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterAttendanceController;
use App\Http\Controllers\AttendanceListController;

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

/*
|--------------------------------------------------------------------------
| ログイン済み + メール認証済み（打刻画面へ遷移）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // 打刻画面表示
    Route::get('/attendance', [RegisterAttendanceController::class, 'index'])
        ->name('attendance.index');

    // 打刻処理
    Route::post('/attendances/clock-in', [RegisterAttendanceController::class, 'clockIn'])
        ->name('attendance.clock_in');

    Route::post('/attendances/clock-out', [RegisterAttendanceController::class, 'clockOut'])
        ->name('attendance.clock_out');

    Route::post('/attendances/break-start', [RegisterAttendanceController::class, 'breakStart'])
        ->name('attendance.break_start');

    Route::post('/attendances/break-end', [RegisterAttendanceController::class, 'breakEnd'])
        ->name('attendance.break_end');

    Route::get('/attendance/list', [AttendanceListController::class, 'index'])->name('attendance_list.index');
    Route::get('/attendance/{attendance}', [AttendanceListController::class, 'show'])->name('attendance_list.show'); // 詳細（とりあえず枠）
});
