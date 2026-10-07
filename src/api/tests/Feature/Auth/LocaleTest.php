<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    // Note: like a browser, Laravel's test client always sends Accept-Language
    // (en-us by default), so every case below sets it explicitly.
    private function failedLogin(array $headers = []): string
    {
        return $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'nope'], $headers)
            ->json('errors.email.0');
    }

    public function test_follows_accept_language_when_supported(): void
    {
        $this->assertSame('These credentials do not match our records.', $this->failedLogin(['Accept-Language' => 'en-US,en;q=0.9']));

        $this->postJson('/api/v1/auth/login', [], ['Accept-Language' => 'en'])->assertHeader('Content-Language', 'en');
    }

    public function test_unsupported_languages_fall_back_to_app_locale_not_the_first_supported(): void
    {
        config(['app.locale' => 'en']);   // 'es' is first in supported_locales

        $this->assertSame('These credentials do not match our records.', $this->failedLogin(['Accept-Language' => 'de-DE,fr;q=0.8']));
    }

    public function test_a_later_supported_language_in_the_list_is_used(): void
    {
        $this->assertSame('El email o la contraseña no son correctos.', $this->failedLogin(['Accept-Language' => 'de-DE,es-AR;q=0.8']));
    }

    public function test_the_users_choice_wins_over_the_browser(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user, 'web')
            ->patchJson('/api/v1/auth/profile', ['email' => 'not-an-email'], ['Accept-Language' => 'es'])
            ->assertJsonPath('errors.email.0', 'The email field must be a valid email address.');
    }

    public function test_user_can_pick_a_supported_locale(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->patchJson('/api/v1/auth/profile', ['locale' => 'en'])
            ->assertOk()->assertJsonPath('data.locale', 'en');

        $this->actingAs($user, 'web')->patchJson('/api/v1/auth/profile', ['locale' => 'fr'])
            ->assertJsonValidationErrors('locale');
    }

    public function test_reset_mail_goes_out_in_the_users_language(): void
    {
        Notification::fake();
        $user = User::factory()->create(['locale' => 'en']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email], ['Accept-Language' => 'es']);

        Notification::assertSentTo($user, ResetPassword::class, fn ($n, $channels, $notifiable, $locale) => $locale === 'en');
    }
}
