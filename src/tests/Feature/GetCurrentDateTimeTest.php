<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCurrentDateTimeTest extends TestCase
{
    use RefreshDatabase;

    use RefreshDatabase;

    /**
     * ID:4-1 日時取得機能
     * 現在日時情報が UI 表示用データとして渡されている
     */
    public function testCurrentDateTimeIsPassedToView(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 8, 9, 5, 0));

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('attendance.index'));

        $response->assertStatus(200);

        // ViewにserverNowが渡されている
        $response->assertViewHas('serverNow');

        // serverNowが現在時刻である
        $response->assertViewHas('serverNow', function ($serverNow) {
            return $serverNow->equalTo(Carbon::now());
        });

        // JSに埋め込まれるISO形式が存在
        $response->assertSee(Carbon::now()->toIso8601String(), false);

        Carbon::setTestNow();
    }
}
