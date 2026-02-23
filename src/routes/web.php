<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterAttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AttendanceChangeRequestController;
use App\Http\Controllers\AdminFortifySessionController;
use App\Http\Controllers\AdminAttendanceListController;

/*
|--------------------------------------------------------------------------
| ゲスト（未ログイン）
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    // ユーザ登録画面
    Route::get('/register', [RegisterController::class, 'index']);

    // ログイン画面（一般）
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

    // 勤怠一覧画面表示
    Route::get('/attendance/list', [AttendanceListController::class, 'index'])->name('attendance_list.index');

    // 勤怠詳細表示
    Route::get('/attendance/{attendance}', [AttendanceDetailController::class, 'show'])->name('attendance_detail.show');

    // 勤怠申請
    Route::post('/stamp_correction_request/list', [AttendanceChangeRequestController::class, 'store'])->name('attendance_change_request.store');

    // 勤怠申請一覧表示
    Route::get('/stamp_correction_request/list', [AttendanceChangeRequestController::class, 'index'])->name('attendance_change_request.index');
});

/*
|--------------------------------------------------------------------------
| 管理者（別guard）
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // 未ログインの管理者だけが見れる
    Route::middleware('guest:admin')->group(function () {

        Route::get('/login', [AdminFortifySessionController::class, 'index'])
            ->name('login');

        // ★ここに fortify.admin を追加
        Route::post('/login', [AdminFortifySessionController::class, 'store'])
            ->middleware('fortify.admin')
            ->name('login.store');
    });

    // ログイン済み管理者だけが見れる
    Route::middleware('auth:admin')->group(function () {

        Route::post('/logout', [AdminFortifySessionController::class, 'destroy'])
            ->middleware('fortify.admin')
            ->name('logout');

        Route::get('/attendance/list', [AdminAttendanceListController::class, 'index'])
            ->name('attendance_list.index');
    });
});
