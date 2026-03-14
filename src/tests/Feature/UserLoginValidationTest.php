<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserLoginValidationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $overrides);
    }

    /**
     * ■ID2-1
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_id2_1_email_is_required(): void
    {
        $response = $this->from('/login')->post('/login', $this->validPayload([
            'email' => '',
        ]));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);

        $this->assertSame(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );
    }

    /**
     * ■ID2-2
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_id2_2_password_is_required(): void
    {
        $response = $this->from('/login')->post('/login', $this->validPayload([
            'password' => '',
        ]));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password']);

        $this->assertSame(
            'パスワードを入力してください',
            session('errors')->first('password')
        );
    }

    /**
     * ■ID2-3
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_id2_3_invalid_credentials_shows_message(): void
    {
        $response = $this->from('/login')->post('/login', $this->validPayload([
            'email' => 'notfound@example.com',
            'password' => 'wrongpassword',
        ]));

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);

        $this->assertSame(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );
    }

    /**
     * ■ID2-4
     * 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function test_id2_4_valid_credentials_logs_in(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', $this->validPayload());

        $this->assertAuthenticatedAs($user, 'web');

        $response->assertRedirect();
    }
}
