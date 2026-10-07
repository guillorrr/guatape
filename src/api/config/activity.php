<?php

/*
|--------------------------------------------------------------------------
| Background activity (job_runs)
|--------------------------------------------------------------------------
|
| JobRunRecorder writes one row per queue job and per scheduled command so the
| "Actividad" page can answer "what ran, when, and did it fail?". This file
| tunes what gets recorded and how long it is kept.
|
*/

return [

    // Job class basenames or command names never recorded (e.g. a job that
    // runs every few seconds and would drown the history).
    'ignore' => [],

    // Scheduled commands that run often and are usually no-ops: their
    // successful runs are not recorded by the scheduler hook (failures still
    // are). They call JobRunRecorder::recordScheduled() when they did work.
    'quiet' => [
        'activity:reap-stale-runs',
    ],

    // Group label by command prefix ("users:import" → "users") or by job class
    // prefix ("UsersImportJob" starts with "Users"). Anything else: "Other".
    'domains' => [
        'activity' => 'System',
    ],

    // Streamed output kept per run (JobRunRecorder::appendLog), in bytes.
    'log_cap' => 16384,

    // Days kept by activity:prune. Failures are kept longer for post-mortems.
    'retention_days' => [
        'completed' => 7,
        'failed' => 30,
    ],

    // A `running` row older than this is a worker that died without closing it
    // (timeout, OOM, restart). Keep it above the longest legitimate job.
    'stale_after_minutes' => 30,

];
