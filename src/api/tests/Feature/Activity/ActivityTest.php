<?php

namespace Tests\Feature\Activity;

use App\Enums\UserRole;
use App\Models\JobRun;
use App\Models\User;
use App\Support\JobRunRecorder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(UserRole::Admin->value);
    }

    public function test_queue_jobs_are_recorded_with_their_outcome(): void
    {
        RecordedJob::dispatch(fail: false);

        try {
            RecordedJob::dispatch(fail: true);
        } catch (\RuntimeException) {
            // sync queue rethrows
        }

        $this->assertDatabaseHas('job_runs', ['name' => 'RecordedJob', 'status' => JobRun::STATUS_COMPLETED, 'summary' => 'did the thing']);
        $this->assertDatabaseHas('job_runs', ['name' => 'RecordedJob', 'status' => JobRun::STATUS_FAILED]);
    }

    public function test_scheduled_commands_are_recorded_and_quiet_successes_skipped(): void
    {
        $schedule = app(Schedule::class);
        $loud = $schedule->command('activity:prune');
        $quiet = $schedule->command('activity:reap-stale-runs');

        event(new ScheduledTaskFinished($loud, 0.25));
        event(new ScheduledTaskFinished($quiet, 0.1));
        event(new ScheduledTaskFailed($quiet, new \RuntimeException('db down')));

        $this->assertDatabaseHas('job_runs', ['name' => 'activity:prune', 'status' => JobRun::STATUS_COMPLETED, 'duration_ms' => 250, 'domain' => 'System']);
        $this->assertDatabaseMissing('job_runs', ['name' => 'activity:reap-stale-runs', 'status' => JobRun::STATUS_COMPLETED]);
        $this->assertDatabaseHas('job_runs', ['name' => 'activity:reap-stale-runs', 'status' => JobRun::STATUS_FAILED]);
    }

    public function test_stale_running_rows_are_reaped(): void
    {
        $stale = JobRun::create(['name' => 'Ghost', 'status' => JobRun::STATUS_RUNNING, 'started_at' => now()->subHours(2)]);
        $alive = JobRun::create(['name' => 'Slow', 'status' => JobRun::STATUS_RUNNING, 'started_at' => now()->subMinutes(5)]);

        Artisan::call('activity:reap-stale-runs');

        $this->assertSame(JobRun::STATUS_FAILED, $stale->fresh()->status);
        $this->assertSame(JobRun::STATUS_RUNNING, $alive->fresh()->status);
    }

    public function test_prune_keeps_failures_longer(): void
    {
        $oldOk = JobRun::create(['name' => 'A', 'status' => JobRun::STATUS_COMPLETED]);
        $oldFail = JobRun::create(['name' => 'B', 'status' => JobRun::STATUS_FAILED]);
        JobRun::whereKey([$oldOk->id, $oldFail->id])->update(['created_at' => now()->subDays(10)]);

        Artisan::call('activity:prune');

        $this->assertModelMissing($oldOk);
        $this->assertModelExists($oldFail);
    }

    public function test_endpoints_require_system_view(): void
    {
        $member = User::factory()->create();
        $member->assignRole(UserRole::Member->value);

        $this->actingAs($member, 'web')->getJson('/api/v1/system/activity')->assertForbidden();
    }

    public function test_history_schedule_and_commands(): void
    {
        JobRun::create(['name' => 'SomeJob', 'status' => JobRun::STATUS_COMPLETED, 'log' => str_repeat('x', 100)]);

        $this->actingAs($this->admin, 'web')->getJson('/api/v1/system/activity?status=completed')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'SomeJob')
            ->assertJsonMissingPath('data.0.log');

        $this->actingAs($this->admin, 'web')->getJson('/api/v1/system/activity/stats')
            ->assertOk()->assertJsonPath('data.completed', 1);

        $schedule = $this->actingAs($this->admin, 'web')->getJson('/api/v1/system/schedule')->assertOk()->json('data');
        $this->assertContains('activity:prune', array_column($schedule, 'name'));

        $commands = $this->actingAs($this->admin, 'web')->getJson('/api/v1/system/commands')->assertOk()->json('data');
        $prune = collect($commands)->firstWhere('name', 'activity:prune');
        $this->assertSame('0 4 * * *', $prune['schedule']['expression']);
        $this->assertSame('System', $prune['domain']);
        $this->assertNotContains('migrate', array_column($commands, 'name'));
    }
}

class RecordedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public bool $fail) {}

    public function handle(): void
    {
        if ($this->fail) {
            throw new \RuntimeException('boom');
        }

        JobRunRecorder::note('did the thing');
    }
}
