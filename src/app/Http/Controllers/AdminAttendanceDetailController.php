<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;

class AdminAttendanceDetailController extends Controller
{
    // 勤怠詳細表示
    public function show(Attendance $attendance)
    {
        // user を確実に読み込む（N+1回避にもなる）
        $attendance->load('user');

        // ここが重要：user_id じゃなくて Userモデルを渡す
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

        // 管理者用なら view パスも admin 側にするのが普通（あなたのBladeに合わせて調整）
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
