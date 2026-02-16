<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AttendanceDetailController extends Controller
{
    // 勤怠詳細表示
    public function show(Attendance $attendance)
    {
        abort_unless($attendance->user_id === Auth::id(), 403);
        $attendance->load('breaks');
        $breaks = $attendance->breaks->values();

        return view('attendance_detail', [
            'attendance' => $attendance,
            'breaks' => $breaks,
            'user' => Auth::user(),
        ]);
    }
}
