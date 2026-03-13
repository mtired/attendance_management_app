<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceChangeRequestStoreRequest; // 既存Requestを流用する場合
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    /**
     * 管理者：勤怠を直接更新（申請は作らない）
     * 既存休憩は「開始・終了が両方空欄なら削除」
     */
    public function update(AdminAttendanceChangeRequestStoreRequest $request)
    {
        $validated = $request->validated();

        $attendance = Attendance::with('breaks')->findOrFail($validated['attendance_id']);

        $date = Carbon::parse($attendance->work_date)->format('Y-m-d');

        DB::transaction(function () use ($attendance, $validated, $date) {

            // =========================
            // 出勤・退勤・備考を更新
            // =========================
            $clockIn = !empty($validated['requested_clock_in_at'])
                ? "{$date} {$validated['requested_clock_in_at']}:00"
                : null;

            $clockOut = !empty($validated['requested_clock_out_at'])
                ? "{$date} {$validated['requested_clock_out_at']}:00"
                : null;

            $attendance->update([
                'clock_in_at'  => $clockIn,
                'clock_out_at' => $clockOut,
                'remarks'      => '',
            ]);

            // =========================
            // 休憩を更新（更新/追加/削除）
            // =========================
            foreach (($validated['requested_breaks'] ?? []) as $b) {
                $targetBreakId = $b['target_break_id'] ?? null;
                $startStr = $b['start'] ?? null;
                $endStr   = $b['end'] ?? null;

                $hasAny = (!empty($startStr) || !empty($endStr));

                // 既存休憩
                if ($targetBreakId) {
                    // 両方空欄 → 削除
                    if (!$hasAny) {
                        $attendance->breaks()->whereKey($targetBreakId)->delete();
                        continue;
                    }

                    // 更新
                    $attendance->breaks()->whereKey($targetBreakId)->update([
                        'break_start_at' => "{$date} {$startStr}:00",
                        'break_end_at'   => "{$date} {$endStr}:00",
                    ]);
                    continue;
                }

                // 新規休憩（targetなし）
                if ($hasAny) {
                    $attendance->breaks()->create([
                        'break_start_at' => "{$date} {$startStr}:00",
                        'break_end_at'   => "{$date} {$endStr}:00",
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.attendance_list.index')
            ->with('message', '勤怠を更新しました。');
    }
}
