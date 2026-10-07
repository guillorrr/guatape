<?php

namespace App\Support;

use App\Models\JobRun;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

/**
 * What the scheduler runs and when, read from the live Schedule
 * (routes/console.php) instead of a hand-kept list that drifts. Each entry is
 * joined with its last recorded run from job_runs.
 *
 * Give scheduled entries a ->description('…'): it is what this catalog shows.
 */
class ScheduleCatalog
{
    /**
     * @return list<array{name: string, description: ?string, expression: string, timezone: string, next_run: ?string, last_run: ?array{status: string, finished_at: ?string, duration_ms: ?int}}>
     */
    public static function all(): array
    {
        // In an HTTP request the console kernel isn't built: routes/console.php
        // hasn't run and Schedule isn't a singleton, so events() would be empty.
        // Listing the commands builds the kernel, which defines the schedule.
        Artisan::all();

        $events = app(Schedule::class)->events();

        $names = array_map(fn (Event $e) => self::name($e), $events);
        $lastRuns = JobRun::query()
            ->whereIn('name', array_filter($names))
            ->whereIn('id', JobRun::query()->selectRaw('MAX(id)')->groupBy('name'))
            ->get()
            ->keyBy('name');

        return array_values(array_map(function (Event $event, ?string $name) use ($lastRuns) {
            $last = $name ? $lastRuns->get($name) : null;

            return [
                'name' => $name ?? 'closure',
                'description' => $event->description,
                'expression' => $event->getExpression(),
                'timezone' => (string) ($event->timezone ?? config('app.timezone')),
                'next_run' => $event->nextRunDate()->toIso8601String(),
                'last_run' => $last ? [
                    'status' => $last->status,
                    'finished_at' => $last->finished_at?->toIso8601String(),
                    'duration_ms' => $last->duration_ms,
                ] : null,
            ];
        }, $events, $names));
    }

    private static function name(Event $event): ?string
    {
        if ($event instanceof CallbackEvent) {
            return $event->description ?: null;
        }

        return JobRunRecorder::commandName($event);
    }
}
