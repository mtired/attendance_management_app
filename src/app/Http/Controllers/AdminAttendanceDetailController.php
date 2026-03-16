<?php

namespace App\Http\Controllers;

use App\Models\Attendance;

class AdminAttendanceDetailController extends Controller
{
    // 勤怠詳細表示
    public function show(Attendance $attendance)
    {
        $attendance->load('user');

        $user = $attendance->user;

        $breaks = $attendance->breaks()->orderBy('break_start_at')->get();

        $pendingRequest = $attendance->changeRequests()
            ->where('status', 0)
            ->latest()
            ->first();

        $hasPendingRequest = (bool) $pendingRequest;

        $displayBreaks = $breaks; // デフォルトは元休憩

        if ($pendingRequest) {
            $requestBreaks = $pendingRequest->requestBreaks()->get(); // attendance_request_breaks

            $changesByTargetId = $requestBreaks
                ->where('status', 1)
                ->keyBy('target_break_id');

            $added = $requestBreaks
                ->where('status', 0)
                ->sortBy('proposed_break_start_at')
                ->values();

            $merged = $breaks->map(function ($b) use ($changesByTargetId) {
                $chg = $changesByTargetId->get($b->id);

                return (object) [
                    'start_at' => $chg?->proposed_break_start_at ?? $b->break_start_at,
                    'end_at'   => $chg?->proposed_break_end_at   ?? $b->break_end_at,
                    'is_added' => false,
                ];
            });

            $addedRows = $added->map(function ($a) {
                return (object) [
                    'start_at' => $a->proposed_break_start_at,
                    'end_at'   => $a->proposed_break_end_at,
                    'is_added' => true,
                ];
            });

            $displayBreaks = $merged->concat($addedRows)->values();
        }

        return view('admin.attendance_detail', compact(
            'attendance',
            'user',
            'breaks',
            'hasPendingRequest',
            'pendingRequest',
            'displayBreaks'
        ));
    }
}
