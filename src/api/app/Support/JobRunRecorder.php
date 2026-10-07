<?php

namespace App\Support;

use App\Models\JobRun;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Queue\Job;

/**
 * Records background-task lifecycle into job_runs, for queue jobs
 * (Queue::before/after/failing) and scheduled commands
 * (ScheduledTaskFinished/Failed). Wired in AppServiceProvider.
 *
 * Best-effort by design: recording must never break the job it observes, so
 * every write swallows its own errors. A running job can describe what it did
 * with note() ("45 users imported") and stream output with appendLog().
 */
class JobRunRecorder
{
    private static ?int $currentId = null;

    // ---- Queue jobs --------------------------------------------------------

    public static function started(Job $job): void
    {
        $name = class_basename($job->resolveName());
        if (self::ignored($name)) {
            return;
        }

        try {
            self::$currentId = JobRun::create([
                'job_uuid' => $job->uuid(),
                'name' => $name,
                'queue' => $job->getQueue(),
                'domain' => self::domain($name),
                'status' => JobRun::STATUS_RUNNING,
                'started_at' => now(),
            ])->id;
        } catch (\Throwable) {
            // best-effort
        }
    }

    public static function finished(Job $job, string $status, ?\Throwable $exception = null): void
    {
        $name = class_basename($job->resolveName());
        if (self::ignored($name)) {
            return;
        }

        try {
            $run = JobRun::where('job_uuid', $job->uuid())
                ->where('status', JobRun::STATUS_RUNNING)
                ->latest('id')
                ->first();
            if ($run) {
                self::close($run, $status, $exception);
            }
        } catch (\Throwable) {
            // best-effort
        } finally {
            self::$currentId = null;
        }
    }

    /** A running job reports what it did (shown in the activity list). */
    public static function note(string $summary): void
    {
        if (! self::$currentId) {
            return;
        }

        try {
            JobRun::whereKey(self::$currentId)->update(['summary' => mb_substr($summary, 0, 255)]);
        } catch (\Throwable) {
            // best-effort
        }
    }

    /**
     * Append streamed output to the running row. Only the last `activity.log_cap`
     * bytes are kept: the tail is what tells where a job got stuck.
     */
    public static function appendLog(string $chunk): void
    {
        if (! self::$currentId || $chunk === '') {
            return;
        }

        try {
            $run = JobRun::find(self::$currentId);
            if (! $run) {
                return;
            }
            $cap = (int) config('activity.log_cap', 16384);
            $log = $run->log ? $run->log."\n".$chunk : $chunk;
            if (strlen($log) > $cap) {
                $log = "…(truncated)…\n".substr($log, -$cap);
            }
            $run->update(['log' => $log]);
        } catch (\Throwable) {
            // best-effort
        }
    }

    /**
     * Fail `running` rows left by a worker that died mid-job (hard timeout, OOM,
     * container restart): those paths never fire Queue::after/failing, so the
     * row would read "running" forever.
     */
    public static function reapStale(int $olderThanMinutes): int
    {
        $stale = JobRun::where('status', JobRun::STATUS_RUNNING)
            ->where('started_at', '<', now()->subMinutes($olderThanMinutes))
            ->get();

        foreach ($stale as $run) {
            self::close($run, JobRun::STATUS_FAILED, null,
                "Reaped by activity:reap-stale-runs: the worker died or timed out without closing this run (older than {$olderThanMinutes} min).");
        }

        return $stale->count();
    }

    public static function countStale(int $olderThanMinutes): int
    {
        return JobRun::where('status', JobRun::STATUS_RUNNING)
            ->where('started_at', '<', now()->subMinutes($olderThanMinutes))
            ->count();
    }

    // ---- Scheduled commands ------------------------------------------------

    public static function scheduledFinished(Event $task, string $status, ?float $runtime = null, ?\Throwable $exception = null): void
    {
        // Schedule::job() events have no command: the queue hooks record them
        // when a worker runs the job.
        $name = self::commandName($task);
        if ($name === null || self::ignored($name)) {
            return;
        }

        if ($status === JobRun::STATUS_COMPLETED && in_array($name, config('activity.quiet', []), true)) {
            return;
        }

        try {
            $durationMs = $runtime !== null ? (int) ($runtime * 1000) : null;
            JobRun::create([
                'name' => $name,
                'queue' => 'scheduler',
                'domain' => self::domain($name),
                'status' => $status,
                'started_at' => $durationMs !== null ? now()->subMilliseconds($durationMs) : now(),
                'finished_at' => now(),
                'duration_ms' => $durationMs,
                'exception' => $exception ? mb_substr((string) $exception, 0, 5000) : null,
            ]);
        } catch (\Throwable) {
            // best-effort
        }
    }

    /**
     * Record a quiet scheduled command that did meaningful work. Called from the
     * command itself, which runs in a different process than the scheduler.
     */
    public static function recordScheduled(string $name, ?string $summary = null, ?int $durationMs = null): void
    {
        try {
            JobRun::create([
                'name' => $name,
                'queue' => 'scheduler',
                'domain' => self::domain($name),
                'status' => JobRun::STATUS_COMPLETED,
                'summary' => $summary ? mb_substr($summary, 0, 255) : null,
                'started_at' => $durationMs !== null ? now()->subMilliseconds($durationMs) : now(),
                'finished_at' => now(),
                'duration_ms' => $durationMs,
            ]);
        } catch (\Throwable) {
            // best-effort
        }
    }

    /** Artisan command name of a scheduled event, or null for closures/jobs. */
    public static function commandName(Event $task): ?string
    {
        if (empty($task->command)) {
            return null;
        }

        return preg_match('/artisan[\'"]?\s+([^\s\'"]+)/', (string) $task->command, $m) ? $m[1] : null;
    }

    /** Group label for a job class or command name (config activity.domains). */
    public static function domain(string $name): string
    {
        $prefix = str_contains($name, ':') ? strstr($name, ':', true) : $name;

        foreach (config('activity.domains', []) as $key => $label) {
            if ($prefix === $key || str_starts_with(strtolower($name), strtolower($key))) {
                return $label;
            }
        }

        return 'Other';
    }

    // ---- Helpers -----------------------------------------------------------

    private static function ignored(string $name): bool
    {
        return in_array($name, config('activity.ignore', []), true);
    }

    private static function close(JobRun $run, string $status, ?\Throwable $exception = null, ?string $reason = null): void
    {
        $finished = now();
        $run->update([
            'status' => $status,
            'finished_at' => $finished,
            'duration_ms' => $run->started_at ? max(0, (int) $run->started_at->diffInMilliseconds($finished)) : null,
            'exception' => $reason ?? ($exception ? mb_substr((string) $exception, 0, 5000) : null),
        ]);
    }
}
