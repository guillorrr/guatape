<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_points_to_the_spa_and_resets_the_password(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://app.example.com']);
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user, &$token) {
            $token = $n->token;
            $url = $n->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://app.example.com/reset-password?token=');
        });

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertOk();

        $this->assertTrue(password_verify('brand-new-pass', $user->fresh()->password));
    }

    public function test_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'bogus', 'email' => $user->email,
            'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass',
        ])->assertJsonValidationErrors('email');
    }
}
