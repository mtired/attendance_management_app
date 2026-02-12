<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceListController extends Controller
{
    /**
     * 勤怠一覧（月表示）
     * /attendance?month=2023-06
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // クエリからmonth取得
        $month = $request->query('month');

        // monthがあればその月、なければ今月とする
        $baseDate = $month
            ? Carbon::createFromFormat('Y-m', $month)
            : Carbon::now();

        // 今月分のデータ取得
        $startOfMonth = $baseDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $baseDate->copy()->endOfMonth()->toDateString();
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->with('breaks')
            ->orderBy('work_date')
            ->get();

        $rows = $this->makeMonthBase($baseDate);
        $rows = $this->mergeAttendance($rows, $attendances);

        // 前月/翌月/今月
        $prevMonth = $baseDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $baseDate->copy()->addMonth()->format('Y-m');
        $currentMonth = $baseDate->format('Y/m');


        return view('attendance_list', [
            'attendances' => $rows,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'currentMonth' => $currentMonth,
        ]);
    }

    // ここから下は「とりあえず枠」：後で詳細画面作るなら実装
    public function show(Attendance $attendance)
    {
        // 認可（他人の勤怠見れないように）
        abort_unless($attendance->user_id === Auth::id(), 403);

        // 詳細画面を作るならここ
        return view('attendance_list.show', compact('attendance'));
    }

    /**
     * 勤怠一覧の該当月の空データ作成
     * ※1日〜月末のデータを作成
     */
    private function makeMonthBase(Carbon $base): array
    {
        $start = $base->copy()->startOfMonth();
        $end   = $base->copy()->endOfMonth();

        $rows = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();

            $rows[$key] = (object)[
                'id' => null,
                'work_date' => $key,
                'work_date_label' => $date->copy()->format('m/d') . '(' .
                    ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek] .
                    ')',
                'clock_in' => '',
                'clock_out' => '',
                'break_time' => '',
                'total_time' => '',
            ];
        }

        return $rows;
    }

    /**
     * 勤怠一覧の空データと合体
     */
    private function mergeAttendance(array $rows, $attendances): array
    {
        foreach ($attendances as $attendance) {
            $key = Carbon::parse($attendance->work_date)->toDateString();
            if (isset($rows[$key])) {

                // Id
                $rows[$key]->id = $attendance->id;

                // 出勤時刻
                $rows[$key]->clock_in =
                    $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '';

                // 退勤時刻
                $rows[$key]->clock_out =
                    $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '';

                // 休憩合計
                $breakMinutes = 0;
                foreach ($attendance->breaks as $break) {
                    if ($break->break_start_at && $break->break_end_at) {
                        $start = Carbon::parse($break->break_start_at);
                        $end   = Carbon::parse($break->break_end_at);
                        $breakMinutes += max(0, $start->diffInMinutes($end));
                    }
                }

                $rows[$key]->break_time_label =
                    $breakMinutes >= 0
                    ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60)
                    : '';

                // 勤怠合計
                if ($attendance->clock_in_at && $attendance->clock_out_at) {

                    $in  = Carbon::parse($attendance->clock_in_at);
                    $out = Carbon::parse($attendance->clock_out_at);
                    $workMinutes = $in->diffInMinutes($out);

                    $netMinutes = max(0, $workMinutes - $breakMinutes);

                    $rows[$key]->total_time_label =
                        sprintf('%d:%02d', intdiv($netMinutes, 60), $netMinutes % 60);
                }
            }
        }

        return array_values($rows);
    }
}
