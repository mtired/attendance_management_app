<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use App\Models\AttendanceRequestBreak;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceChangeRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function createAttendanceWithBreak(User $user, string $date = '2026-03-10'): Attendance
    {
        $workDate = Carbon::parse($date);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'clock_in_at' => $workDate->copy()->setTime(9, 0),
            'clock_out_at' => $workDate->copy()->setTime(18, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $workDate->copy()->setTime(12, 0),
            'break_end_at' => $workDate->copy()->setTime(13, 0),
        ]);

        return $attendance;
    }

    /**
     * ID:15-1
     * 承認待ちの修正申請が全て表示されている
     */
    public function testPendingTabDisplaysAllPendingRequests(): void
    {
        $admin = Admin::factory()->create();

        $user1 = User::factory()->create(['name' => '山田 太郎']);
        $user2 = User::factory()->create(['name' => '佐藤 花子']);

        $attendance1 = $this->createAttendanceWithBreak($user1, '2026-03-10');
        $attendance2 = $this->createAttendanceWithBreak($user2, '2026-03-11');

        $pendingA = AttendanceChangeRequest::create([
            'requested_by' => $user1->id,
            'attendance_id' => $attendance1->id,
            'proposed_clock_in_at' => '2026-03-10 08:30:00',
            'proposed_clock_out_at' => '2026-03-10 17:30:00',
            'remarks' => '申請A',
            'status' => 0,
        ]);

        $pendingB = AttendanceChangeRequest::create([
            'requested_by' => $user2->id,
            'attendance_id' => $attendance2->id,
            'proposed_clock_in_at' => '2026-03-11 08:45:00',
            'proposed_clock_out_at' => '2026-03-11 17:45:00',
            'remarks' => '申請B',
            'status' => 0,
        ]);

        $approved = AttendanceChangeRequest::create([
            'requested_by' => $user1->id,
            'attendance_id' => $attendance1->id,
            'proposed_clock_in_at' => '2026-03-10 08:00:00',
            'proposed_clock_out_at' => '2026-03-10 17:00:00',
            'remarks' => '承認済み申請',
            'status' => 1,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_change_request.index', ['tab' => 'pending']));

        $response->assertOk();

        // 承認待ちのタブ
        $response->assertViewHas('activeTab', 'pending');

        $response->assertSee('承認待ち');
        $response->assertSee('山田 太郎');
        $response->assertSee('佐藤 花子');
        $response->assertSee('申請A');
        $response->assertSee('申請B');
        $response->assertSee('2026/03/10');
        $response->assertSee('2026/03/11');

        // View に渡された pending データが正しいこと
        $response->assertViewHas('pendingRequests', function ($requests) use ($pendingA, $pendingB) {
            return $requests->pluck('id')->contains($pendingA->id)
                && $requests->pluck('id')->contains($pendingB->id)
                && $requests->count() === 2;
        });

        // approved データが正しいこと
        $response->assertViewHas('approvedRequests', function ($requests) use ($approved) {
            return $requests->pluck('id')->contains($approved->id)
                && $requests->count() === 1;
        });
    }

    /**
     * ID:15-2
     * 承認済みの修正申請が全て表示されている
     */
    public function testApprovedTabDisplaysAllApprovedRequests(): void
    {
        $admin = Admin::factory()->create();

        $user1 = User::factory()->create(['name' => '山田 太郎']);
        $user2 = User::factory()->create(['name' => '佐藤 花子']);

        $attendance1 = $this->createAttendanceWithBreak($user1, '2026-03-10');
        $attendance2 = $this->createAttendanceWithBreak($user2, '2026-03-11');

        $approvedA = AttendanceChangeRequest::create([
            'requested_by' => $user1->id,
            'attendance_id' => $attendance1->id,
            'proposed_clock_in_at' => '2026-03-10 08:30:00',
            'proposed_clock_out_at' => '2026-03-10 17:30:00',
            'remarks' => '承認済みA',
            'status' => 1,
        ]);

        $approvedB = AttendanceChangeRequest::create([
            'requested_by' => $user2->id,
            'attendance_id' => $attendance2->id,
            'proposed_clock_in_at' => '2026-03-11 08:45:00',
            'proposed_clock_out_at' => '2026-03-11 17:45:00',
            'remarks' => '承認済みB',
            'status' => 1,
        ]);

        $pending = AttendanceChangeRequest::create([
            'requested_by' => $user1->id,
            'attendance_id' => $attendance1->id,
            'proposed_clock_in_at' => '2026-03-10 08:00:00',
            'proposed_clock_out_at' => '2026-03-10 17:00:00',
            'remarks' => '承認待ち申請',
            'status' => 0,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_change_request.index', ['tab' => 'approved']));

        $response->assertOk();

        // 承認済みのタブ
        $response->assertViewHas('activeTab', 'approved');

        $response->assertSee('承認済み');
        $response->assertSee('山田 太郎');
        $response->assertSee('佐藤 花子');
        $response->assertSee('承認済みA');
        $response->assertSee('承認済みB');
        $response->assertSee('2026/03/10');
        $response->assertSee('2026/03/11');

        // View に渡された approved データが正しいこと
        $response->assertViewHas('approvedRequests', function ($requests) use ($approvedA, $approvedB) {
            return $requests->pluck('id')->contains($approvedA->id)
                && $requests->pluck('id')->contains($approvedB->id)
                && $requests->count() === 2;
        });

        // pending データが正しいこと
        $response->assertViewHas('pendingRequests', function ($requests) use ($pending) {
            return $requests->pluck('id')->contains($pending->id)
                && $requests->count() === 1;
        });
    }

    /**
     * ID:15-3
     * 修正申請の詳細内容が正しく表示されている
     */
    public function testRequestDetailDisplaysCorrectRequestContent(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create(['name' => '山田 太郎']);

        $attendance = $this->createAttendanceWithBreak($user, '2026-03-10');
        $break = $attendance->breaks()->first();

        $request = AttendanceChangeRequest::create([
            'requested_by' => $user->id,
            'attendance_id' => $attendance->id,
            'proposed_clock_in_at' => '2026-03-10 08:30:00',
            'proposed_clock_out_at' => '2026-03-10 17:30:00',
            'remarks' => '電車遅延のため修正申請',
            'status' => 0,
        ]);

        // 既存休憩を変更
        AttendanceRequestBreak::create([
            'request_id' => $request->id,
            'target_break_id' => $break->id,
            'status' => AttendanceRequestBreak::ACTION_UPDATE,
            'proposed_break_start_at' => '2026-03-10 12:15:00',
            'proposed_break_end_at' => '2026-03-10 13:15:00',
        ]);

        // 追加休憩
        AttendanceRequestBreak::create([
            'request_id' => $request->id,
            'target_break_id' => null,
            'status' => AttendanceRequestBreak::ACTION_ADD,
            'proposed_break_start_at' => '2026-03-10 15:00:00',
            'proposed_break_end_at' => '2026-03-10 15:15:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.request_detail.show', ['attendanceChangeRequest' => $request->id]));

        $response->assertOk();
        $response->assertSee('勤怠詳細');
        $response->assertSee('山田 太郎');
        $response->assertSee('2026年');
        $response->assertSee('3月10日');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
        $response->assertSee('12:15');
        $response->assertSee('13:15');
        $response->assertSee('15:00');
        $response->assertSee('15:15');
        $response->assertSee('電車遅延のため修正申請');
        $response->assertSee('承認');
    }

    /**
     * ID:15-4
     * 修正申請の承認処理が正しく行われる
     */
    public function testApproveRequestUpdatesAttendanceAndBreaksAndMarksRequestApproved(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create(['name' => '山田 太郎']);

        $attendance = $this->createAttendanceWithBreak($user, '2026-03-10');
        $originalBreak = $attendance->breaks()->first();

        $request = AttendanceChangeRequest::create([
            'requested_by' => $user->id,
            'attendance_id' => $attendance->id,
            'proposed_clock_in_at' => '2026-03-10 08:30:00',
            'proposed_clock_out_at' => '2026-03-10 17:30:00',
            'remarks' => '承認テスト申請',
            'status' => AttendanceChangeRequest::STATUS_PENDING,
        ]);

        // 既存休憩の更新
        AttendanceRequestBreak::create([
            'request_id' => $request->id,
            'target_break_id' => $originalBreak->id,
            'status' => AttendanceRequestBreak::ACTION_UPDATE,
            'proposed_break_start_at' => '2026-03-10 12:15:00',
            'proposed_break_end_at' => '2026-03-10 13:15:00',
        ]);

        // 新規休憩の追加
        AttendanceRequestBreak::create([
            'request_id' => $request->id,
            'target_break_id' => null,
            'status' => AttendanceRequestBreak::ACTION_ADD,
            'proposed_break_start_at' => '2026-03-10 15:00:00',
            'proposed_break_end_at' => '2026-03-10 15:15:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.request_detail.approve', ['attendanceChangeRequest' => $request->id]));

        $response->assertRedirect(
            route('admin.attendance_change_request.index', ['tab' => 'pending'])
        );

        $request->refresh();
        $attendance->refresh();
        $originalBreak->refresh();

        $this->assertSame(AttendanceChangeRequest::STATUS_APPROVED, $request->status);
        $this->assertSame('08:30', $attendance->clock_in_at->format('H:i'));
        $this->assertSame('17:30', $attendance->clock_out_at->format('H:i'));

        // 既存休憩が更新されること
        $this->assertSame('12:15', $originalBreak->break_start_at->format('H:i'));
        $this->assertSame('13:15', $originalBreak->break_end_at->format('H:i'));

        // 追加休憩が新規作成されること
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => '2026-03-10 15:00:00',
            'break_end_at' => '2026-03-10 15:15:00',
        ]);
    }
}
