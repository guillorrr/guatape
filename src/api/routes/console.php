<?php

use Illuminate\Support\Facades\Schedule;

// Scheduled tasks. The scheduler container runs `php artisan schedule:work`.
// Give every entry a ->description(): the Activity page lists it (ScheduleCatalog).

Schedule::command('activity:prune')
    ->dailyAt('04:00')
    ->withoutOverlapping(10)
    ->description('Deletes old background-activity history.');

Schedule::command('activity:reap-stale-runs')
    ->everyTenMinutes()
    ->withoutOverlapping(5)
    ->description('Closes runs left "running" by a worker that died.');
