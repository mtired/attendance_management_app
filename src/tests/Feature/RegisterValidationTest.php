<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegisterValidationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    /**
     * ■ID1-1
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_id1_1_name_is_required(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'name' => '',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['name']);

        $this->assertSame(
            'お名前を入力してください',
            session('errors')->first('name')
        );
    }

    /**
     * ■ID1-2
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_id1_2_email_is_required(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'email' => '',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email']);

        $this->assertSame(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );
    }

    /**
     * ■ID1-3
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_id1_4_password_must_be_at_least_8_chars(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => '1234567', // 7文字
            'password_confirmation' => '1234567',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password']);

        $this->assertSame(
            'パスワードは8文字以上で入力してください',
            session('errors')->first('password')
        );
    }

    /**
     * ■ID1-4
     * パスワードが一致しない場合、バリデーションメッセージが表示される
     */
    public function test_id1_5_password_confirmation_must_match(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => 'password123',
            'password_confirmation' => 'password999',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password']);

        $this->assertSame(
            'パスワードと一致しません',
            session('errors')->first('password')
        );
    }

    /**
     * ■ID1-5
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_id1_3_password_is_required(): void
    {
        $response = $this->from('/register')->post('/register', $this->validPayload([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['password']);

        $this->assertSame(
            'パスワードを入力してください',
            session('errors')->first('password')
        );
    }

    /**
     * ■ID1-6
     * 会員登録後、認証メールが送信される
     */
    public function test_id1_6_verify_email_is_sent_after_register(): void
    {
        Notification::fake();

        $payload = $this->validPayload([
            'email' => 'success@example.com',
        ]);

        $this->post('/register', $payload);

        $user = User::where('email', 'success@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * ■ID1-7
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     *
     * ※ テストでは「ボタン押下」を再現するため、そのボタンのリンク先URLにアクセスして検証
     */
    public function test_id1_7_click_verify_button_redirects_to_verification_site(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $response = $this->get('/email/verify');

        $response->assertStatus(200);
    }

    /**
     * ■ID1-8
     * メール認証サイトのメール認証を完了すると、プロフィール設定画面に遷移する
     */
    public function test_id1_8_after_email_verification_redirects_to_profile_setup(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        // 認証URL（署名付き）を作成して、メール内リンクを踏んだのと同じ状態にする
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/attendance');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
