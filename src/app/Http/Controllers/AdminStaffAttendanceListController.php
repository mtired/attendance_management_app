<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffAttendanceListController extends Controller
{
    /**
     * 勤怠一覧（月表示）
     * /attendance?month=20XX-XX
     */
    public function index(Request $request, User $user)
    {
        // 月取得
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


        return view('admin.staff_attendance_list', [
            'user' => $user,
            'attendances' => $rows,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'currentMonth' => $currentMonth,
        ]);
    }

    public function exportCsv(Request $request, User $user): StreamedResponse
    {
        $month = $request->query('month');

        $baseDate = $month
            ? Carbon::createFromFormat('Y-m', $month)
            : Carbon::now();

        $startOfMonth = $baseDate->copy()->startOfMonth()->toDateString();
        $endOfMonth   = $baseDate->copy()->endOfMonth()->toDateString();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->with('breaks')
            ->orderBy('work_date')
            ->get();

        $rows = $this->makeMonthBase($baseDate);
        $rows = $this->mergeAttendance($rows, $attendances);

        $fileName = 'staff_attendance_' . $user->id . '_' . $baseDate->format('Ym') . '.csv';

        return response()->streamDownload(function () use ($user, $baseDate, $rows) {
            $out = fopen('php://output', 'w');

            // Excelで文字化け対策
            fwrite($out, "\xEF\xBB\xBF");

            // ヘッダー
            fputcsv($out, [
                '氏名',
                '対象月',
                '日付',
                '出勤',
                '退勤',
                '休憩',
                '合計',
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $user->name,
                    $baseDate->format('Y-m'),
                    $r->work_date,                 // 例: 2026-02-01
                    $r->clock_in ?? '',
                    $r->clock_out ?? '',
                    $r->break_time_label ?? '',
                    $r->total_time_label ?? '',
                ]);
            }

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
