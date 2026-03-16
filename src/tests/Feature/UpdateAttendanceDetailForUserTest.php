<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceChangeRequest;
use App\Models\BreakTime;
use App\Models\User;
use App\Models\Admin;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAttendanceDetailForUserTest extends TestCase
{
    use RefreshDatabase;

    private function createAttendanceWithBreak(User $user): Attendance
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 18, 0, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::create(2026, 3, 10, 12, 0, 0),
            'break_end_at' => Carbon::create(2026, 3, 10, 13, 0, 0),
        ]);

        return $attendance;
    }

    private function validPayload(Attendance $attendance): array
    {
        $break = $attendance->breaks()->first();

        return [
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => '09:00',
            'requested_clock_out_at' => '18:00',
            'requested_remarks' => '電車遅延のため修正申請',
            'requested_breaks' => [
                [
                    'target_break_id' => $break?->id,
                    'start' => '12:00',
                    'end' => '13:00',
                ],
            ],
        ];
    }

    /**
     * ID:11-1
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testValidationErrorIsShownWhenClockInIsLaterThanClockOut(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_clock_in_at'] = '19:00';
        $payload['requested_clock_out_at'] = '18:00';

        $response = $this->actingAs($user)
            ->from(route('attendance_detail.show', $attendance->id))
            ->post(route('attendance_change_request.store'), $payload);

        $response->assertRedirect(route('attendance_detail.show', $attendance->id));

        $followed = $this->actingAs($user)->get(route('attendance_detail.show', $attendance->id));
        $followed->assertSee('出勤時間が不適切な値です');
    }

    /**
     * ID:11-2
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testValidationErrorIsShownWhenBreakStartIsLaterThanClockOut(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_breaks'][0]['start'] = '18:30';
        $payload['requested_breaks'][0]['end'] = '18:45';

        $response = $this->actingAs($user)
            ->from(route('attendance_detail.show', $attendance->id))
            ->post(route('attendance_change_request.store'), $payload);

        $response->assertRedirect(route('attendance_detail.show', $attendance->id));

        $followed = $this->actingAs($user)->get(route('attendance_detail.show', $attendance->id));
        $followed->assertSee('休憩時間が不適切な値です');
    }

    /**
     * ID:11-3
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testValidationErrorIsShownWhenBreakEndIsLaterThanClockOut(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_breaks'][0]['start'] = '17:30';
        $payload['requested_breaks'][0]['end'] = '18:30';

        $response = $this->actingAs($user)
            ->from(route('attendance_detail.show', $attendance->id))
            ->post(route('attendance_change_request.store'), $payload);

        $response->assertRedirect(route('attendance_detail.show', $attendance->id));

        $followed = $this->actingAs($user)->get(route('attendance_detail.show', $attendance->id));
        $followed->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    /**
     * ID:11-4
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function testValidationErrorIsShownWhenRemarksIsEmpty(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_remarks'] = '';

        $response = $this->actingAs($user)
            ->from(route('attendance_detail.show', $attendance->id))
            ->post(route('attendance_change_request.store'), $payload);

        $response->assertRedirect(route('attendance_detail.show', $attendance->id));

        $followed = $this->actingAs($user)->get(route('attendance_detail.show', $attendance->id));
        $followed->assertSee('備考を記入してください');
    }

    /**
     * ID:11-5
     * 修正申請処理が実行される
     */
    public function testChangeRequestIsCreatedAndDisplayedOnAdminScreens(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $admin = Admin::factory()->create();

        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_clock_in_at'] = '08:30';
        $payload['requested_clock_out_at'] = '17:30';
        $payload['requested_remarks'] = '電車遅延のため修正申請';
        $storeResponse = $this->actingAs($user)
            ->post(route('attendance_change_request.store'), $payload);

        $storeResponse->assertRedirect(route('attendance_change_request.index'));

        $request = AttendanceChangeRequest::latest('id')->first();

        $this->assertNotNull($request);
        $this->assertSame($user->id, $request->requested_by);
        $this->assertSame($attendance->id, $request->attendance_id);
        $this->assertSame('電車遅延のため修正申請', $request->remarks);
        $this->assertSame(0, $request->status);

        // 管理者の申請詳細画面（ここで時刻を見る）
        $adminDetailResponse = $this->actingAs($admin, 'admin')
            ->get(route('admin.request_detail.show', [
                'attendanceChangeRequest' => $request->id,
            ]));

        $adminDetailResponse->assertOk();
        $adminDetailResponse->assertSee('勤怠詳細');
        $adminDetailResponse->assertSee('山田 太郎');
        $adminDetailResponse->assertSee('08:30');
        $adminDetailResponse->assertSee('17:30');
        $adminDetailResponse->assertSee('電車遅延のため修正申請');

        // 管理者の申請一覧画面（ここでは日付と申請理由を見る）
        $adminListResponse = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_change_request.index', ['tab' => 'pending']));

        $adminListResponse->assertStatus(200);
        $adminListResponse->assertSee('承認待ち');
        $adminListResponse->assertSee('山田 太郎');
        $adminListResponse->assertSee('2026/03/10');
        $adminListResponse->assertSee('電車遅延のため修正申請');
    }

    /**
     * ID:11-6
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function testPendingTabShowsAllOwnRequests(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $otherUser = User::factory()->create([
            'name' => '他人 ユーザー',
        ]);

        $attendance1 = $this->createAttendanceWithBreak($user);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 11),
            'clock_in_at' => Carbon::create(2026, 3, 11, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 11, 18, 0, 0),
        ]);

        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'work_date' => Carbon::create(2026, 3, 12),
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0),
        ]);

        AttendanceChangeRequest::create([
            'requested_by' => $user->id,
            'attendance_id' => $attendance1->id,
            'proposed_clock_in_at' => '2026-03-10 08:30:00',
            'proposed_clock_out_at' => '2026-03-10 17:30:00',
            'remarks' => '申請A',
            'status' => 0,
        ]);

        AttendanceChangeRequest::create([
            'requested_by' => $user->id,
            'attendance_id' => $attendance2->id,
            'proposed_clock_in_at' => '2026-03-11 08:45:00',
            'proposed_clock_out_at' => '2026-03-11 17:45:00',
            'remarks' => '申請B',
            'status' => 0,
        ]);

        AttendanceChangeRequest::create([
            'requested_by' => $otherUser->id,
            'attendance_id' => $otherAttendance->id,
            'proposed_clock_in_at' => '2026-03-12 10:00:00',
            'proposed_clock_out_at' => '2026-03-12 19:00:00',
            'remarks' => '他人の申請',
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get(route('attendance_change_request.index', ['tab' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('山田 太郎');
        $response->assertSee('申請A');
        $response->assertSee('申請B');
        $response->assertDontSee('他人の申請');
    }

    /**
     * ID:11-7
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function testApprovedTabShowsRequestsApprovedByAdmin(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $admin = Admin::factory()->create();

        $attendance = $this->createAttendanceWithBreak($user);

        $request = AttendanceChangeRequest::create([
            'requested_by' => $user->id,
            'attendance_id' => $attendance->id,
            'proposed_clock_in_at' => '2026-03-10 08:30:00',
            'proposed_clock_out_at' => '2026-03-10 17:30:00',
            'remarks' => '承認対象申請',
            'status' => 0,
        ]);

        // 管理者が承認
        $approveResponse = $this->actingAs($admin, 'admin')
            ->post(route('admin.request_detail.approve', ['attendanceChangeRequest' => $request->id]));

        $approveResponse->assertRedirect(route('admin.attendance_change_request.index', ['tab' => 'pending']));

        $request->refresh();
        $attendance->refresh();

        $this->assertSame(1, $request->status);
        $this->assertSame('08:30', $attendance->clock_in_at->format('H:i'));
        $this->assertSame('17:30', $attendance->clock_out_at->format('H:i'));

        // 一般ユーザーの承認済み一覧
        $userApprovedResponse = $this->actingAs($user)
            ->get(route('attendance_change_request.index', ['tab' => 'approved']));

        $userApprovedResponse->assertStatus(200);
        $userApprovedResponse->assertSee('承認済み');
        $userApprovedResponse->assertSee('承認対象申請');

        // 管理者の承認済み一覧
        $adminApprovedResponse = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_change_request.index', ['tab' => 'approved']));

        $adminApprovedResponse->assertStatus(200);
        $adminApprovedResponse->assertSee('承認済み');
        $adminApprovedResponse->assertSee('山田 太郎');
        $adminApprovedResponse->assertSee('承認対象申請');
    }

    /**
     * ID:11-8
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function testDetailLinkMovesToAttendanceDetailScreen(): void
    {
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $request = AttendanceChangeRequest::create([
            'requested_by' => $user->id,
            'attendance_id' => $attendance->id,
            'proposed_clock_in_at' => '2026-03-10 08:30:00',
            'proposed_clock_out_at' => '2026-03-10 17:30:00',
            'remarks' => '詳細確認用申請',
            'status' => 0,
        ]);

        $listResponse = $this->actingAs($user)
            ->get(route('attendance_change_request.index', ['tab' => 'pending']));

        $listResponse->assertStatus(200);
        $listResponse->assertSee(
            route('attendance_detail.show', [
                'attendance' => $attendance->id,
                'request' => $request->id,
            ]),
            false
        );

        $detailResponse = $this->actingAs($user)
            ->get(route('attendance_detail.show', [
                'attendance' => $attendance->id,
                'request' => $request->id,
            ]));

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('承認待ちのため修正はできません。');
        $detailResponse->assertSee('08:30');
        $detailResponse->assertSee('17:30');
        $detailResponse->assertSee('詳細確認用申請');
    }
}
