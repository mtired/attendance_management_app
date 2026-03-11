<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:5-1
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function testStatusIsDisplayedAsOutOfWork(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('status', '勤務外');
        $response->assertSee('勤務外');

        Carbon::setTestNow();
    }

    /**
     * ID:5-2
     * 出勤中の場合、勤怠ステータスが正しく表示される
     *
     * ※現状コードでは「勤務中」が表示されるため、
     *   テストも「勤務中」で確認しています。
     */
    public function testStatusIsDisplayedAsWorking(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0));

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => now(),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('status', '出勤中');
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    /**
     * ID:5-3
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function testStatusIsDisplayedAsOnBreak(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => null,
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now(),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('status', '休憩中');
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    /**
     * ID:5-4
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function testStatusIsDisplayedAsFinishedWork(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 18, 0, 0));

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in_at' => now()->copy()->setTime(9, 0),
            'clock_out_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('status', '退勤済');
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }
}
