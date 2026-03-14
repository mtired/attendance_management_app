<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class GetAttendanceListForAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:12-1
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function testAllUsersAttendanceForTheDayIsDisplayedCorrectly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));

        $admin = Admin::factory()->create();

        $user1 = User::factory()->create(['name' => '山田 太郎']);
        $user2 = User::factory()->create(['name' => '佐藤 花子']);
        $otherDayUser = User::factory()->create(['name' => '別日 ユーザー']);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 18, 0, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance1->id,
            'break_start_at' => Carbon::create(2026, 3, 10, 12, 0, 0),
            'break_end_at' => Carbon::create(2026, 3, 10, 13, 0, 0),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 10, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 19, 0, 0),
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => Carbon::create(2026, 3, 10, 15, 0, 0),
            'break_end_at' => Carbon::create(2026, 3, 10, 15, 30, 0),
        ]);

        // 別日の勤怠は当日一覧に出ない
        Attendance::factory()->create([
            'user_id' => $otherDayUser->id,
            'work_date' => Carbon::create(2026, 3, 11),
            'clock_in_at' => Carbon::create(2026, 3, 11, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 11, 18, 0, 0),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_list.index'));

        $response->assertOk();

        // user1: 09:00-18:00 / 休憩1:00 / 合計8:00
        $response->assertSee('山田 太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        // user2: 10:00-19:00 / 休憩0:30 / 合計8:30
        $response->assertSee('佐藤 花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('0:30');
        $response->assertSee('8:30');

        // 別日のユーザーは表示されない
        $response->assertDontSee('別日 ユーザー');

        Carbon::setTestNow();
    }

    /**
     * ID:12-2
     * 遷移した際に現在の日付が表示される
     */
    public function testCurrentDateIsDisplayedWhenOpeningAdminAttendanceList(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));

        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_list.index'));

        $response->assertOk();
        $response->assertViewHas('currentMonth', '2026/03/10');
        $response->assertSee('2026/03/10');

        Carbon::setTestNow();
    }

    /**
     * ID:12-3
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function testPreviousDayAttendanceIsDisplayedWhenPreviousDayIsSelected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));

        $admin = Admin::factory()->create();
        $prevUser = User::factory()->create(['name' => '前日 ユーザー']);
        $todayUser = User::factory()->create(['name' => '当日 ユーザー']);

        Attendance::factory()->create([
            'user_id' => $prevUser->id,
            'work_date' => Carbon::create(2026, 3, 9),
            'clock_in_at' => Carbon::create(2026, 3, 9, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 9, 18, 0, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $todayUser->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 10, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 19, 0, 0),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_list.index', [
                'month' => '2026-03-09',
            ]));

        $response->assertOk();
        $response->assertViewHas('currentMonth', '2026/03/09');
        $response->assertSee('2026/03/09');
        $response->assertSee('前日 ユーザー');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertDontSee('当日 ユーザー');

        Carbon::setTestNow();
    }

    /**
     * ID:12-4
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function testNextDayAttendanceIsDisplayedWhenNextDayIsSelected(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 10, 0, 0));

        $admin = Admin::factory()->create();
        $todayUser = User::factory()->create(['name' => '当日 ユーザー']);
        $nextUser = User::factory()->create(['name' => '翌日 ユーザー']);

        Attendance::factory()->create([
            'user_id' => $todayUser->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 18, 0, 0),
        ]);

        Attendance::factory()->create([
            'user_id' => $nextUser->id,
            'work_date' => Carbon::create(2026, 3, 11),
            'clock_in_at' => Carbon::create(2026, 3, 11, 11, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 11, 20, 0, 0),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_list.index', [
                'month' => '2026-03-11',
            ]));

        $response->assertOk();
        $response->assertViewHas('currentMonth', '2026/03/11');
        $response->assertSee('2026/03/11');
        $response->assertSee('翌日 ユーザー');
        $response->assertSee('11:00');
        $response->assertSee('20:00');
        $response->assertDontSee('当日 ユーザー');

        Carbon::setTestNow();
    }
}
