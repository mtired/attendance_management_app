<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetUserAttendanceDetailForAdminTest extends TestCase
{
    use RefreshDatabase;

    private function createAttendanceWithBreak(User $user): Attendance
    {
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 18, 0, 0),
            'remarks' => '通常勤務',
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
            'requested_remarks' => '管理者修正',
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
     * ID:13-1
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function testAdminAttendanceDetailShowsSelectedAttendanceData(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_detail.show', $attendance->id));

        $response->assertOk();
        $response->assertSee('勤怠詳細');
        $response->assertSee('山田 太郎');
        $response->assertSee('2026年');
        $response->assertSee('3月10日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('通常勤務');
    }

    /**
     * ID:13-2
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testValidationErrorIsShownWhenClockInIsLaterThanClockOutForAdmin(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_clock_in_at'] = '19:00';
        $payload['requested_clock_out_at'] = '18:00';

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance_detail.show', $attendance->id))
            ->post(route('admin.attendance.update'), $payload);

        $response->assertRedirect(route('admin.attendance_detail.show', $attendance->id));

        $followed = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_detail.show', $attendance->id));

        $followed->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * ID:13-3
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testValidationErrorIsShownWhenBreakStartIsLaterThanClockOutForAdmin(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_breaks'][0]['start'] = '18:30';
        $payload['requested_breaks'][0]['end'] = '18:45';

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance_detail.show', $attendance->id))
            ->post(route('admin.attendance.update'), $payload);

        $response->assertRedirect(route('admin.attendance_detail.show', $attendance->id));

        $followed = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_detail.show', $attendance->id));

        $followed->assertSee('休憩時間が不適切な値です');
    }

    /**
     * ID:13-4
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function testValidationErrorIsShownWhenBreakEndIsLaterThanClockOutForAdmin(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_breaks'][0]['start'] = '17:30';
        $payload['requested_breaks'][0]['end'] = '18:30';

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance_detail.show', $attendance->id))
            ->post(route('admin.attendance.update'), $payload);

        $response->assertRedirect(route('admin.attendance_detail.show', $attendance->id));

        $followed = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_detail.show', $attendance->id));

        $followed->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    /**
     * ID:13-5
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function testValidationErrorIsShownWhenRemarksIsEmptyForAdmin(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $attendance = $this->createAttendanceWithBreak($user);

        $payload = $this->validPayload($attendance);
        $payload['requested_remarks'] = '';

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.attendance_detail.show', $attendance->id))
            ->post(route('admin.attendance.update'), $payload);

        $response->assertRedirect(route('admin.attendance_detail.show', $attendance->id));

        $followed = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_detail.show', $attendance->id));

        $followed->assertSee('備考を記入してください');
    }
}
