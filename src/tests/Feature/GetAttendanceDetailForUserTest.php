<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class GetAttendanceDetailForUserTest extends TestCase
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

    /**
     * ID:10-1
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function testAttendanceDetailShowsLoggedInUserName(): void
    {
        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)
            ->get(route('attendance_detail.show', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('名前');
        $response->assertSee('山田 太郎');
    }

    /**
     * ID:10-2
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function testAttendanceDetailShowsSelectedDate(): void
    {
        $user = User::factory()->create();

        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)
            ->get(route('attendance_detail.show', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('日付');
        $response->assertSee('2026年');
        $response->assertSee('3月10日');
    }

    /**
     * ID:10-3
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function testAttendanceDetailShowsCorrectClockInAndClockOutTime(): void
    {
        $user = User::factory()->create();

        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)
            ->get(route('attendance_detail.show', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('出勤・退勤');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * ID:10-4
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function testAttendanceDetailShowsCorrectBreakTime(): void
    {
        $user = User::factory()->create();

        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)
            ->get(route('attendance_detail.show', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee('休憩');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
