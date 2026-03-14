<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {

            // 30日分作成
            for ($i = 0; $i < 30; $i++) {

                $workDate = Carbon::today()->subDays($i);

                // 出勤時間（8:45〜9:15）
                $clockIn = (clone $workDate)
                    ->setTime(9, 0)
                    ->addMinutes(rand(-15, 15));

                // 退勤時間（17:30〜18:30）
                $clockOut = (clone $workDate)
                    ->setTime(18, 0)
                    ->addMinutes(rand(-30, 30));

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $workDate->toDateString(),
                    'clock_in_at' => $clockIn,
                    'clock_out_at' => $clockOut,
                ]);

                // 休憩（1〜2回）
                $breakCount = rand(1, 2);

                for ($j = 0; $j < $breakCount; $j++) {

                    $breakStart = (clone $clockIn)
                        ->addHours(3)
                        ->addMinutes($j * 60);

                    $breakEnd = (clone $breakStart)->addMinutes(30);

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start_at' => $breakStart,
                        'break_end_at' => $breakEnd,
                    ]);
                }
            }
        }
    }
}
