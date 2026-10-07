<?php

namespace App\Console\Commands;

use App\Support\JobRunRecorder;
use Illuminate\Console\Command;

class ReapStaleJobRunsCommand extends Command
{
    protected $signature = 'activity:reap-stale-runs
                            {--minutes= : Minimum age of a `running` row to consider it dead (default: activity.stale_after_minutes)}
                            {--dry-run : Only report how many would be reaped}';

    protected $description = 'Marks as failed the runs stuck in "running" because their worker died without closing them (timeout, out of memory, restart). Only touches rows older than the threshold, so a slow but alive job is never reaped.';

    public function handle(): int
    {
        $start = microtime(true);
        $minutes = max(1, (int) ($this->option('minutes') ?: config('activity.stale_after_minutes', 30)));

        if ($this->option('dry-run')) {
            $this->info(JobRunRecorder::countStale($minutes)." run(s) older than {$minutes} min would be reaped.");

            return self::SUCCESS;
        }

        $reaped = JobRunRecorder::reapStale($minutes);
        $this->info("Reaped {$reaped} stale run(s).");

        // Quiet command (config activity.quiet): record only when it did work.
        if ($reaped > 0) {
            JobRunRecorder::recordScheduled('activity:reap-stale-runs', "{$reaped} stale run(s) closed", (int) ((microtime(true) - $start) * 1000));
        }

        return self::SUCCESS;
    }
}
