<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One execution of a queue job or a scheduled command (see JobRunRecorder).
 */
class JobRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'job_uuid',
        'name',
        'queue',
        'domain',
        'status',
        'summary',
        'started_at',
        'finished_at',
        'duration_ms',
        'exception',
        'log',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
