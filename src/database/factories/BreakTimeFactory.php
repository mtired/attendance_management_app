<?php

namespace Database\Factories;

use App\Models\BreakTime;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BreakTime>
 */
class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition(): array
    {
        $start = Carbon::today()->setTime(12, 0);
        $end   = Carbon::today()->setTime(13, 0);

        return [
            'attendance_id' => Attendance::factory(),
            'break_start_at' => $start,
            'break_end_at' => $end,
        ];
    }

    /**
     * 休憩中
     */
    public function onBreak(): static
    {
        return $this->state(fn() => [
            'break_start_at' => Carbon::now(),
            'break_end_at' => null,
        ]);
    }
}
