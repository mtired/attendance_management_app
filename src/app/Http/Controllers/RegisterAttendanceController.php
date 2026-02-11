<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;

class RegisterAttendanceController extends Controller
{
    /**
     * 勤務開始打刻
     */
    public function index()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        // ステータス確認
        $status = '勤務外';
        $isOnBreak = false;

        if ($attendance) {
            $isOnBreak = $attendance
                ? $attendance->breaks()->whereNull('break_end_at')->exists()
                : false;

            if ($attendance->clock_out_at) {
                $status = '退勤済';
            } elseif ($isOnBreak) {
                $status = '休憩中';
            } elseif ($attendance->clock_in_at) {
                $status = '勤務中';
            }
        }
        $serverNow = now(); // サーバー時刻（JST）

        return view('register_attendance', compact('attendance', 'status', 'serverNow'));
    }

    /**
     * 勤務開始打刻
     */
    public function clockIn()
    {
        $user = Auth::user();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    /**
     * 勤務終了打刻
     */
    public function clockOut()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        $attendance->update([
            'clock_out_at' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    /**
     * 休憩開始表示
     */
    public function breakStart()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    /**
     * 休憩終了表示
     */
    public function breakEnd()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();

        $break = $attendance->breaks()
            ->whereNull('break_end_at')
            ->orderByDesc('break_start_at')
            ->first();

        $break->update([
            'break_end_at' => now(),
        ]);

        return redirect()->route('attendance.index');
    }
}
