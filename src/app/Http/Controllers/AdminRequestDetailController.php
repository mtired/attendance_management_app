<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceChangeRequest;
use App\Models\BreakTime;

class AdminRequestDetailController extends Controller
{
    // 申請詳細表示（承認済み/承認待ちをタブに分けて表示）
    public function show(AttendanceChangeRequest $attendanceChangeRequest)
    {
        $attendanceChangeRequest->load([
            'requestedBy',
            'requestBreaks',
            'attendance.user',
            'attendance.breaks',
        ]);

        $displayRequest = $attendanceChangeRequest;         // ★表示対象はこの申請に固定
        $attendance     = $attendanceChangeRequest->attendance;
        $user           = $attendance->user;

        // 元の休憩（勤怠に紐づく休憩）
        $breaks = $attendance->breaks()->orderBy('break_start_at')->get();

        $hasRequest = true;
        $isPending  = ($displayRequest->status === 0);

        // 申請休憩（変更・追加）
        $requestBreaks = $displayRequest->requestBreaks;

        $changesByTargetId = $requestBreaks
            ->where('status', 1)
            ->keyBy('target_break_id');

        $added = $requestBreaks
            ->where('status', 0)
            ->sortBy('proposed_break_start_at')
            ->values();

        // 元休憩をベースに「変更があれば差し替え」
        $merged = $breaks->map(function ($b) use ($changesByTargetId) {
            $chg = $changesByTargetId->get($b->id);

            return (object) [
                'start_at' => $chg?->proposed_break_start_at ?? $b->break_start_at,
                'end_at'   => $chg?->proposed_break_end_at   ?? $b->break_end_at,
                'is_added' => false,
            ];
        });

        // 追加休憩
        $addedRows = $added->map(function ($a) {
            return (object) [
                'start_at' => $a->proposed_break_start_at,
                'end_at'   => $a->proposed_break_end_at,
                'is_added' => true,
            ];
        });

        // 表示用休憩（元＋追加）
        $displayBreaks = $merged->concat($addedRows)->values();

        // 同じ start/end が二重に入っていたら表示で潰す
        $displayBreaks = $displayBreaks
            ->unique(fn($b) => ($b->start_at ?? '') . '|' . ($b->end_at ?? ''))
            ->values();

        return view('admin.request_detail', compact(
            'attendance',
            'user',
            'breaks',
            'displayRequest',
            'hasRequest',
            'isPending',
            'displayBreaks'
        ));
    }

    // 承認（この申請1件を承認）
    public function approve(Request $request, AttendanceChangeRequest $attendanceChangeRequest)
    {
        // 承認待ち以外は承認させない
        abort_unless($attendanceChangeRequest->status === 0, 409);

        return DB::transaction(function () use ($attendanceChangeRequest) {

            $attendanceChangeRequest->load([
                'requestBreaks',
                'attendance.breaks',
            ]);

            $pendingRequest = $attendanceChangeRequest; // ★これが承認対象
            $attendance = $attendanceChangeRequest->attendance;

            // 勤怠（出退勤・備考）更新
            $attendance->update([
                'clock_in_at'  => $pendingRequest->proposed_clock_in_at,
                'clock_out_at' => $pendingRequest->proposed_clock_out_at,
                'remarks'      => $pendingRequest->remarks,
            ]);

            $requestBreaks = $pendingRequest->requestBreaks;

            // 変更（status=1）：target_break_id を更新
            foreach ($requestBreaks->where('status', 1) as $rb) {
                if (!$rb->target_break_id) continue;

                $break = $attendance->breaks()->where('id', $rb->target_break_id)->first();
                if (!$break) continue;

                $break->update([
                    'break_start_at' => $rb->proposed_break_start_at,
                    'break_end_at'   => $rb->proposed_break_end_at,
                ]);
            }

            // 追加（status=0）：新規作成
            foreach ($requestBreaks->where('status', 0) as $rb) {

                $exists = $attendance->breaks()
                    ->where('break_start_at', $rb->proposed_break_start_at)
                    ->where('break_end_at', $rb->proposed_break_end_at)
                    ->exists();

                if ($exists) continue;

                $attendance->breaks()->create([
                    'user_id'        => $attendance->user_id,
                    'break_start_at' => $rb->proposed_break_start_at,
                    'break_end_at'   => $rb->proposed_break_end_at,
                ]);
            }

            // 承認済みに更新
            $pendingRequest->update(['status' => 1]);

            return redirect()->route('admin.attendance_change_request.index', ['tab' => 'pending']);
        });
    }
}
