<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use App\Models\BreakTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceDetailController extends Controller
{
    public function show(Request $request, Attendance $attendance)
    {
        $user = Auth::user();

        // 元の勤怠休憩
        $breaks = BreakTime::where('attendance_id', $attendance->id)->get();

        // Bladeで使う初期値
        $pendingRequest = null;
        $hasPendingRequest = false;

        $targetRequest = null;
        $isRequestDetail = false;
        $displayBreaks = collect();

        // 申請一覧から来た場合（pending / approved 共通）
        if ($request->filled('request')) {
            $targetRequest = AttendanceChangeRequest::with('requestBreaks')
                ->where('id', $request->query('request'))
                ->where('attendance_id', $attendance->id)
                ->firstOrFail();

            $isRequestDetail = true;

            $displayBreaks = $targetRequest->requestBreaks->map(function ($b) {
                return (object) [
                    'start_at' => $b->proposed_break_start_at,
                    'end_at'   => $b->proposed_break_end_at,
                ];
            });
        } else {
            // 勤怠一覧から来た場合：未承認申請があればそれを表示
            $pendingRequest = AttendanceChangeRequest::with('requestBreaks')
                ->where('attendance_id', $attendance->id)
                ->where('status', AttendanceChangeRequest::STATUS_PENDING)
                ->latest()
                ->first();

            if ($pendingRequest) {
                $hasPendingRequest = true;
                $targetRequest = $pendingRequest;

                $displayBreaks = $pendingRequest->requestBreaks->map(function ($b) {
                    return (object) [
                        'start_at' => $b->proposed_break_start_at,
                        'end_at'   => $b->proposed_break_end_at,
                    ];
                });
            }
        }

        return view('attendance_detail', compact(
            'attendance',
            'user',
            'breaks',
            'pendingRequest',
            'hasPendingRequest',
            'targetRequest',
            'isRequestDetail',
            'displayBreaks',
        ));
    }
}
