<?php

namespace App\Console\Commands;

use App\Models\JobRun;
use Illuminate\Console\Command;

class PruneJobRunsCommand extends Command
{
    protected $signature = 'activity:prune';

    protected $description = 'Deletes old background-activity history: completed runs after activity.retention_days.completed (7), failed and stale ones after .failed (30). Failures are kept longer because they are the ones investigated.';

    public function handle(): int
    {
        $completed = JobRun::where('status', JobRun::STATUS_COMPLETED)
            ->where('created_at', '<', now()->subDays((int) config('activity.retention_days.completed', 7)))
            ->delete();

        $rest = JobRun::whereIn('status', [JobRun::STATUS_FAILED, JobRun::STATUS_RUNNING])
            ->where('created_at', '<', now()->subDays((int) config('activity.retention_days.failed', 30)))
            ->delete();

        $this->info("Pruned {$completed} completed and {$rest} failed/stale runs.");

        return self::SUCCESS;
    }
}
