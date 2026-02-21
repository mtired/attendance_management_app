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

        $user = Auth::user();

        $breaks = $attendance->breaks()->orderBy('break_start_at')->get();

        $pendingRequest = $attendance->changeRequests()
            ->where('status', 'pending')
            ->latest()
            ->first();

        $hasPendingRequest = (bool) $pendingRequest;

        $displayBreaks = $breaks; // デフォルトは元休憩

        if ($pendingRequest) {
            $requestBreaks = $pendingRequest->requestBreaks()->get(); // attendance_request_breaks

            // status=1（変更）: target_break_id をキーにして差し替えできるようにする
            $changesByTargetId = $requestBreaks
                ->where('status', 1)
                ->keyBy('target_break_id');

            // status=0（追加）: 追加分だけ抽出
            $added = $requestBreaks
                ->where('status', 0)
                ->sortBy('proposed_break_start_at')
                ->values();

            // 元休憩を「変更があれば proposed に差し替え」して並べる
            $merged = $breaks->map(function ($b) use ($changesByTargetId) {
                $chg = $changesByTargetId->get($b->id);

                return (object) [
                    'start_at' => $chg?->proposed_break_start_at ?? $b->break_start_at,
                    'end_at'   => $chg?->proposed_break_end_at   ?? $b->break_end_at,
                    'is_added' => false,
                ];
            });

            // 末尾に追加分を足す
            $addedRows = $added->map(function ($a) {
                return (object) [
                    'start_at' => $a->proposed_break_start_at,
                    'end_at'   => $a->proposed_break_end_at,
                    'is_added' => true,
                ];
            });

            $displayBreaks = $merged->concat($addedRows)->values();
        }

        return view('attendance_detail', compact(
            'attendance',
            'user',
            'breaks',
            'hasPendingRequest',
            'pendingRequest',
            'displayBreaks'
        ));
    }
}
