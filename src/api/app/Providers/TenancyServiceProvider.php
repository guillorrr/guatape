<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Tenancy;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Tenancy::class);
    }

    public function boot(): void
    {
        // Super admins can do everything (they also bypass permission middleware).
        Gate::before(fn (User $user) => $user->is_super_admin ? true : null);

        $tenancy = $this->app->make(Tenancy::class);

        // A job dispatched inside a tenant runs inside that same tenant: the id
        // travels in the payload and is restored before the job is handled.
        // Registered always and inert while tenancy is off, so it can be
        // switched on at runtime (tests) without re-booting.
        Queue::createPayloadUsing(fn () => $tenancy->enabled() ? ['tenant_id' => $tenancy->id()] : []);

        Event::listen(JobProcessing::class, function (JobProcessing $event) use ($tenancy) {
            if (! $tenancy->enabled()) {
                return;
            }
            $id = $event->job->payload()['tenant_id'] ?? null;
            $tenancy->set($id ? Tenant::find($id) : null);
        });
        // Must return nothing: a listener returning false stops the event, and
        // the JobRunRecorder listener after this one would never see the job end.
        $reset = function () use ($tenancy): void {
            if ($tenancy->enabled()) {
                $tenancy->set(null);
            }
        };
        Event::listen(JobProcessed::class, $reset);
        Event::listen(JobFailed::class, $reset);
    }
}
