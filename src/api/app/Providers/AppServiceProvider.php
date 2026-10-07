<?php

namespace App\Providers;

use App\Models\JobRun;
use App\Models\User;
use App\Support\DuplicateKeyResponder;
use App\Support\JobRunRecorder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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

        $this->recordBackgroundActivity();

        DuplicateKeyResponder::resolveOwnerUsing('users', fn (string $column, string $value) => User::where($column, $value)->first(['id', 'name']));
    }

    /**
     * Every queue job and scheduled command leaves a row in job_runs (see
     * JobRunRecorder and config/activity.php), shown on the Activity page.
     */
    private function recordBackgroundActivity(): void
    {
        Queue::before(fn (JobProcessing $e) => JobRunRecorder::started($e->job));
        Queue::after(fn (JobProcessed $e) => JobRunRecorder::finished($e->job, JobRun::STATUS_COMPLETED));
        Queue::failing(fn (JobFailed $e) => JobRunRecorder::finished($e->job, JobRun::STATUS_FAILED, $e->exception));

        Event::listen(ScheduledTaskFinished::class, fn (ScheduledTaskFinished $e) => JobRunRecorder::scheduledFinished($e->task, JobRun::STATUS_COMPLETED, $e->runtime));
        Event::listen(ScheduledTaskFailed::class, fn (ScheduledTaskFailed $e) => JobRunRecorder::scheduledFinished($e->task, JobRun::STATUS_FAILED, null, $e->exception));
    }
}
