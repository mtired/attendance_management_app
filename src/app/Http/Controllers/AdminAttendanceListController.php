<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceListController extends Controller
{
    public function index(Request $request)
    {
        $dateStr = $request->query('month');

        $targetDate = $dateStr
            ? Carbon::parse($dateStr)->startOfDay()
            : now()->startOfDay();

        $prevMonth = $targetDate->copy()->subDay()->format('Y-m-d');
        $nextMonth = $targetDate->copy()->addDay()->format('Y-m-d');
        $currentMonth = $targetDate->format('Y/m/d');

        $attendances = Attendance::with(['user', 'breaks'])
            ->whereDate('work_date', $targetDate->toDateString())
            ->get()
            ->map(function ($attendance) {

                $clockIn = $attendance->clock_in_at
                    ? Carbon::parse($attendance->clock_in_at)
                    : null;

                $clockOut = $attendance->clock_out_at
                    ? Carbon::parse($attendance->clock_out_at)
                    : null;

                // -----------------------
                // 休憩時間合計
                // -----------------------
                $breakMinutes = 0;

                foreach ($attendance->breaks as $break) {
                    if ($break->break_start_at && $break->break_end_at) {
                        $start = Carbon::parse($break->break_start_at);
                        $end   = Carbon::parse($break->break_end_at);

                        $breakMinutes += $start->diffInMinutes($end);
                    }
                }

                $breakTimeLabel = $breakMinutes > 0
                    ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60)
                    : '';

                // -----------------------
                // 実労働時間
                // -----------------------
                $totalTimeLabel = '';

                if ($clockIn && $clockOut) {
                    $workMinutes = $clockIn->diffInMinutes($clockOut);
                    $netMinutes = max(0, $workMinutes - $breakMinutes);

                    $totalTimeLabel = sprintf(
                        '%d:%02d',
                        intdiv($netMinutes, 60),
                        $netMinutes % 60
                    );
                }

                return (object)[
                    'id' => $attendance->id,
                    'name' => $attendance->user->name,
                    'clock_in' => $clockIn ? $clockIn->format('H:i') : '',
                    'clock_out' => $clockOut ? $clockOut->format('H:i') : '',
                    'break_time_label' => $breakTimeLabel,
                    'total_time_label' => $totalTimeLabel,
                ];
            });

        return view('admin.attendance_list', compact(
            'attendances',
            'prevMonth',
            'currentMonth',
            'nextMonth',
        ));
    }
}
