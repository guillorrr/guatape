<?php

namespace App\Providers;

use App\Models\User;
use App\Support\DuplicateKeyResponder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->uncompromised()
            : Password::min(8));

        // Password reset emails open the SPA, which posts back to /auth/reset-password.
        ResetPassword::createUrlUsing(fn (User $user, string $token) => rtrim(config('app.frontend_url'), '/')
            .'/reset-password?'.http_build_query(['token' => $token, 'email' => $user->getEmailForPasswordReset()]));

        DuplicateKeyResponder::resolveOwnerUsing('users', fn (string $column, string $value) => User::where($column, $value)->first(['id', 'name']));
    }
}
