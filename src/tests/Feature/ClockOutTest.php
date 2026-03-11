<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID:8-1
     * 退勤ボタンが正しく機能する
     */
    public function testClockOutButtonWorksCorrectly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 18, 0, 0));

        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0),
            'clock_out_at' => null,
        ]);

        // 1. ステータスが勤務中のユーザーで勤怠打刻画面を開く
        $response = $this->actingAs($user)->get(route('attendance.index'));

        // 2. 「退勤」ボタンが表示されていることを確認
        $response->assertStatus(200);
        $response->assertViewHas('status', '出勤中');
        $response->assertSee('退勤');

        // 3. 退勤処理を行う
        $postResponse = $this->actingAs($user)->post(route('attendance.clock_out'));
        $postResponse->assertRedirect(route('attendance.index'));

        // 退勤時刻が保存されていることを確認
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', Carbon::today())
            ->latest('id')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_out_at);
        $this->assertSame('18:00', $attendance->clock_out_at->format('H:i'));

        // 処理後、ステータスが「退勤済」になることを確認
        $afterResponse = $this->actingAs($user)->get(route('attendance.index'));

        $afterResponse->assertStatus(200);
        $afterResponse->assertViewHas('status', '退勤済');
        $afterResponse->assertSee('退勤済');

        Carbon::setTestNow();
    }

    /**
     * ID:8-2
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function testClockOutTimeCanBeConfirmedOnAttendanceListScreen(): void
    {
        $user = User::factory()->create();

        // 1. ステータスが勤務外のユーザーにログイン
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0, 0));
        $this->actingAs($user)->post(route('attendance.clock_in'));

        // 2. 出勤と退勤の処理を行う
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 18, 0, 0));
        $this->actingAs($user)->post(route('attendance.clock_out'));

        // DBに保存された勤怠を確認
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', Carbon::create(2026, 3, 10))
            ->latest('id')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->clock_in_at);
        $this->assertNotNull($attendance->clock_out_at);
        $this->assertSame('18:00', $attendance->clock_out_at->format('H:i'));

        // 3. 勤怠一覧画面から退勤の日付を確認する
        $response = $this->actingAs($user)->get(route('attendance_list.index'));

        $response->assertStatus(200);

        // 一覧画面に退勤時刻が表示されることを確認
        $response->assertSee('18:00');

        Carbon::setTestNow();
    }
}
