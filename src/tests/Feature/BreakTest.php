<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:7-1
     * 休憩ボタンが正しく機能する
     */
    public function testBreakStartButtonWorksCorrectly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('status', '出勤中');
        $response->assertSee('休憩入');

        $postResponse = $this->actingAs($user)->post(route('attendance.break_start'));
        $postResponse->assertRedirect(route('attendance.index'));

        $break = BreakTime::query()->latest('id')->first();

        $this->assertNotNull($break);
        $this->assertNotNull($break->break_start_at);
        $this->assertNull($break->break_end_at);

        $afterResponse = $this->actingAs($user)->get(route('attendance.index'));

        $afterResponse->assertStatus(200);
        $afterResponse->assertViewHas('status', '休憩中');
        $afterResponse->assertSee('休憩中');
        $afterResponse->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    /**
     * ID:7-2
     * 休憩は一日に何回でもできる
     */
    public function testBreakStartButtonIsDisplayedAgainAfterBreakEnd(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        $this->actingAs($user)->post(route('attendance.break_start'));

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 15, 0));
        $this->actingAs($user)->post(route('attendance.break_end'));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('status', '出勤中');
        $response->assertSee('休憩入');

        Carbon::setTestNow();
    }

    /**
     * ID:7-3
     * 休憩戻ボタンが正しく機能する
     */
    public function testBreakEndButtonWorksCorrectly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 11, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => Carbon::create(2026, 3, 10, 10, 30, 0),
            'break_end_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('status', '休憩中');
        $response->assertSee('休憩戻');

        $postResponse = $this->actingAs($user)->post(route('attendance.break_end'));
        $postResponse->assertRedirect(route('attendance.index'));

        $break = BreakTime::query()->latest('id')->first();

        $this->assertNotNull($break);
        $this->assertNotNull($break->break_end_at);

        $afterResponse = $this->actingAs($user)->get(route('attendance.index'));

        $afterResponse->assertStatus(200);
        $afterResponse->assertViewHas('status', '出勤中');
        $afterResponse->assertSee('出勤中');
        $afterResponse->assertSee('休憩入');

        Carbon::setTestNow();
    }

    /**
     * ID:7-4
     * 休憩戻は一日に何回でもできる
     */
    public function testBreakEndButtonIsDisplayedAgainAfterSecondBreakStart(): void
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));
        $this->actingAs($user)->post(route('attendance.break_start'));

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 15, 0));
        $this->actingAs($user)->post(route('attendance.break_end'));

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 15, 0, 0));
        $this->actingAs($user)->post(route('attendance.break_start'));

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertStatus(200);
        $response->assertViewHas('status', '休憩中');
        $response->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    /**
     * ID:7-5
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function testBreakTimeIsDisplayedOnAttendanceList(): void
    {
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0, 0));
        $this->actingAs($user)->post(route('attendance.break_start'));

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 30, 0));
        $this->actingAs($user)->post(route('attendance.break_end'));

        $response = $this->actingAs($user)->get(route('attendance_list.index'));

        $response->assertStatus(200);

        // 30分休憩なので一覧画面では 0:30
        $response->assertSee('0:30');

        Carbon::setTestNow();
    }
}
