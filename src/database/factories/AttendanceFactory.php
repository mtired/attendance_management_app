<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $start = Carbon::today()->setTime(9, 0);
        $end   = Carbon::today()->setTime(18, 0);

        return [
            'user_id' => User::factory(),
            'work_date' => $start->toDateString(),
            'clock_in_at' => $start,
            'clock_out_at' => $end,
        ];
    }

    /**
     * 出勤中（退勤していない）
     */
    public function working(): static
    {
        return $this->state(fn() => [
            'clock_in_at' => Carbon::today()->setTime(9, 0),
            'clock_out_at' => null,
        ]);
    }

    /**
     * 退勤済
     */
    public function finished(): static
    {
        return $this->state(fn() => [
            'clock_in_at' => Carbon::today()->setTime(9, 0),
            'clock_out_at' => Carbon::today()->setTime(18, 0),
        ]);
    }
}
