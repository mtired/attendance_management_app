<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffListAndAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:14-1
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function testAdminCanSeeAllUsersNamesAndEmails(): void
    {
        $admin = Admin::factory()->create();

        $user1 = User::factory()->create([
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤 花子',
            'email' => 'sato@example.com',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff_list.index'));

        $response->assertOk();
        $response->assertSee('スタッフ一覧');
        $response->assertSee('山田 太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('佐藤 花子');
        $response->assertSee('sato@example.com');
    }

    /**
     * ID:14-2
     * ユーザーの勤怠情報が正しく表示される
     */
    public function testSelectedUsersAttendanceIsDisplayedCorrectly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

        $otherUser = User::factory()->create([
            'name' => '別ユーザー',
        ]);

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

        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'work_date' => Carbon::create(2026, 3, 10),
            'clock_in_at' => Carbon::create(2026, 3, 10, 8, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 17, 0, 0),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff_attendance_list.index', ['user' => $user->id]));

        $response->assertOk();
        $response->assertSee('山田 太郎 さんの勤怠');
        $response->assertSee('03/10(火)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertDontSee('別ユーザー');

        Carbon::setTestNow();
    }

    /**
     * ID:14-3
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function testPreviousMonthInformationIsDisplayedForSelectedUser(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

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

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff_attendance_list.index', [
                'user' => $user->id,
                'month' => '2026-02',
            ]));

        $response->assertOk();
        $response->assertViewHas('currentMonth', '2026/02');
        $response->assertSee('2026/02');
        $response->assertSee('02/10(火)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertDontSee('03/10(火)');

        Carbon::setTestNow();
    }

    /**
     * ID:14-4
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function testNextMonthInformationIsDisplayedForSelectedUser(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

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

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff_attendance_list.index', [
                'user' => $user->id,
                'month' => '2026-04',
            ]));

        $response->assertOk();
        $response->assertViewHas('currentMonth', '2026/04');
        $response->assertSee('2026/04');
        $response->assertSee('04/10(金)');
        $response->assertSee('11:00');
        $response->assertSee('20:00');
        $response->assertDontSee('03/10(火)');

        Carbon::setTestNow();
    }

    /**
     * ID:14-5
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function testDetailLinkMovesToAdminAttendanceDetailScreen(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $admin = Admin::factory()->create();

        $user = User::factory()->create([
            'name' => '山田 太郎',
        ]);

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

        $listResponse = $this->actingAs($admin, 'admin')
            ->get(route('admin.staff_attendance_list.index', ['user' => $user->id]));

        $listResponse->assertOk();
        $listResponse->assertSee(route('admin.attendance_detail.show', $attendance->id), false);

        $detailResponse = $this->actingAs($admin, 'admin')
            ->get(route('admin.attendance_detail.show', $attendance->id));

        $detailResponse->assertOk();
        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee('山田 太郎');
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');

        Carbon::setTestNow();
    }
}
