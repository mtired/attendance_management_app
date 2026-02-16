<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AttendanceChangeRequest;
use App\Models\Attendance;
use App\Http\Requests\AttendanceChangeRequestStoreRequest;

class AttendanceChangeRequestController extends Controller
{
    //
    public function store(AttendanceChangeRequestStoreRequest $request)
    {
        $validated = $request->validated();

        $attendance = Attendance::findOrFail($validated['attendance_id']);
        abort_unless($attendance->user_id === Auth::id(), 403);

        $date = Carbon::parse($attendance->work_date)->format('Y-m-d');

        $clockIn  = "{$date} {$validated['requested_clock_in_at']}:00";
        $clockOut = "{$date} {$validated['requested_clock_out_at']}:00";

        $changeRequest = AttendanceChangeRequest::create([
            'requested_by' => Auth::id(),
            'attendance_id' => $attendance->id,
            'proposed_clock_in_at' => $clockIn,
            'proposed_clock_out_at' => $clockOut,
            'remarks' => $validated['requested_remarks'] ?? null,
            'status' => 0,
        ]);

        foreach (($validated['requested_breaks'] ?? []) as $b) {
            if (empty($b['start']) && empty($b['end'])) continue;

            // start/end 片方だけは Request 側で弾く想定
            $start = "{$date} {$b['start']}:00";
            $end   = "{$date} {$b['end']}:00";

            $targetBreakId = $b['target_break_id'] ?? null;

            $changeRequest->requestBreaks()->create([
                'target_break_id' => $targetBreakId,
                'status' => $targetBreakId ? 1 : 0,
                'proposed_break_start_at' => $start,
                'proposed_break_end_at' => $end,
            ]);
        }

        return redirect()->route('attendance_list.index');
    }
}
