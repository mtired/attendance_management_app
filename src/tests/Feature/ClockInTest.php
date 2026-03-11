<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:6-1
     * 出勤ボタンが正しく機能する
     */
    public function testClockInButtonWorksCorrectly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0));

        $user = User::factory()->create();

        // 1. ステータスが勤務外のユーザーで勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(route('attendance.index'));

        // 2. 「出勤」ボタンが表示されていることを確認
        $response->assertStatus(200);
        $response->assertViewHas('status', '勤務外');
        $response->assertSee('出勤');

        // 3. 出勤処理を行う
        $postResponse = $this->actingAs($user)->post(route('attendance.clock_in'));
        $postResponse->assertRedirect(route('attendance.index'));

        // 出勤データが作成されていることを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('2026-03-10', $attendance->work_date->format('Y-m-d'));
        $this->assertNotNull($attendance->clock_in_at);
        $this->assertNull($attendance->clock_out_at);

        // 処理後、ステータスが「勤務中」になることを確認
        $afterResponse = $this->actingAs($user)->get(route('attendance.index'));
        $afterResponse->assertStatus(200);
        $afterResponse->assertViewHas('status', '勤務中');
        $afterResponse->assertSee('勤務中');

        Carbon::setTestNow();
    }

    /**
     * ID:6-2
     * 出勤は一日一回のみできる
     */
    public function testClockInButtonIsNotDisplayedWhenUserAlreadyFinishedWork(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 18, 0, 0));

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => Carbon::create(2026, 3, 10, 18, 0, 0),
        ]);

        // 退勤済みユーザーで勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(route('attendance.index'));

        // 「出勤」ボタンが表示されないことを確認
        $response->assertStatus(200);
        $response->assertViewHas('status', '退勤済');
        $response->assertDontSee('出勤');

        Carbon::setTestNow();
    }

    /**
     * ID:6-3
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function testClockInTimeCanBeConfirmedOnAttendanceListScreen(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 5, 0));

        $user = User::factory()->create();

        // 1. ステータスが勤務外のユーザーにログイン
        // 2. 出勤処理を行う
        $this->actingAs($user)->post(route('attendance.clock_in'));

        // DBに保存された勤怠を確認
        $attendance = Attendance::where('user_id', $user->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('2026-03-10', $attendance->work_date->format('Y-m-d'));
        $this->assertSame('09:05', $attendance->clock_in_at->format('H:i'));

        // 3. 勤怠一覧画面から出勤の日付を確認する
        $response = $this->actingAs($user)->get(route('attendance_list.index'));

        $response->assertStatus(200);

        // 当日の出勤時刻が一覧画面に表示されることを確認
        $response->assertSee('09:05');

        Carbon::setTestNow();
    }
}
