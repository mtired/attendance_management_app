<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAttendanceListForUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:9-1
     * 自分が行った勤怠情報が全て表示されている
     */
    public function testAllOwnAttendanceInformationIsDisplayed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 1),
            'clock_in_at' => Carbon::create(2026, 3, 1, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 1, 18, 0, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance1->id,
            'break_start_at' => Carbon::create(2026, 3, 1, 12, 0, 0),
            'break_end_at' => Carbon::create(2026, 3, 1, 13, 0, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 2),
            'clock_in_at' => Carbon::create(2026, 3, 2, 10, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 2, 19, 0, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'work_date' => Carbon::create(2026, 3, 3),
            'clock_in_at' => Carbon::create(2026, 3, 3, 8, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 3, 17, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance_list.index'));

        $response->assertStatus(200);

        // 自分の勤怠情報
        $response->assertSee('03/01(日)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        $response->assertSee('03/02(月)');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('0:00');
        $response->assertSee('9:00');

        // 他人の勤怠時刻は表示されない
        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');

        Carbon::setTestNow();
    }

    /**
     * ID:9-2
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function testCurrentMonthIsDisplayedWhenOpeningAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('attendance_list.index'));

        $response->assertStatus(200);
        $response->assertViewHas('currentMonth', '2026/03');
        $response->assertSee('2026/03');

        Carbon::setTestNow();
    }

    /**
     * ID:9-3
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function testPreviousMonthInformationIsDisplayedWhenPreviousMonthIsSelected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 2, 10),
            'clock_in_at' => Carbon::create(2026, 2, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 2, 10, 18, 0, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 10, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 19, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance_list.index', [
            'month' => '2026-02',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('currentMonth', '2026/02');
        $response->assertSee('2026/02');
        $response->assertSee('02/10(火)');
        $response->assertSee('09:00');
        $response->assertDontSee('03/10(火)');

        Carbon::setTestNow();
    }

    /**
     * ID:9-4
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function testNextMonthInformationIsDisplayedWhenNextMonthIsSelected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 18, 0, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 4, 10),
            'clock_in_at' => Carbon::create(2026, 4, 10, 11, 0, 0),
            'clock_out_at' => Carbon::create(2026, 4, 10, 20, 0, 0),
        ]);

        $response = $this->actingAs($user)->get(route('attendance_list.index', [
            'month' => '2026-04',
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('currentMonth', '2026/04');
        $response->assertSee('2026/04');
        $response->assertSee('04/10(金)');
        $response->assertSee('11:00');
        $response->assertDontSee('03/10(火)');

        Carbon::setTestNow();
    }

    /**
     * ID:9-5
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function testUserCanMoveToAttendanceDetailPageFromAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 18, 0, 0),
        ]);

        $listResponse = $this->actingAs($user)->get(route('attendance_list.index'));

        $listResponse->assertStatus(200);
        $listResponse->assertSee(route('attendance_detail.show', $attendance->id), false);

        $detailResponse = $this->actingAs($user)->get(route('attendance_detail.show', $attendance->id));

        $detailResponse->assertStatus(200);

        Carbon::setTestNow();
    }
}
