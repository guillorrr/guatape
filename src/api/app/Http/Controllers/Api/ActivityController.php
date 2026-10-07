<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobRun;
use App\Support\CommandCatalog;
use App\Support\ListQuery;
use App\Support\ScheduleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Read-only view of background work: run history (job_runs), what the
 * scheduler runs, and the catalog of the app's own artisan commands.
 */
class ActivityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        // The log can be large; it is only sent by show().
        $query = JobRun::query()->select(array_diff((new JobRun)->getFillable(), ['log']))->addSelect('id', 'created_at');

        $runs = ListQuery::for($query, $request)
            ->search(['name', 'summary'])
            ->sortable(['id', 'name', 'started_at', 'duration_ms'], default: 'id', dir: 'desc')
            ->filter('status', fn ($q, string $status) => $q->where('status', $status))
            ->filter('domain', fn ($q, string $domain) => $q->where('domain', $domain))
            ->paginate();

        return JsonResource::collection($runs);
    }

    public function show(JobRun $run): JsonResource
    {
        return new JsonResource($run);
    }

    /** Last-24h counts by status and by domain, plus what is running now. */
    public function stats(): JsonResponse
    {
        $since = now()->subDay();

        $byStatus = JobRun::where('created_at', '>=', $since)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $byDomain = JobRun::where('created_at', '>=', $since)
            ->selectRaw('domain, status, COUNT(*) as c')
            ->groupBy('domain', 'status')
            ->get()
            ->groupBy('domain')
            ->map(fn ($rows) => [
                'total' => (int) $rows->sum('c'),
                'failed' => (int) $rows->where('status', JobRun::STATUS_FAILED)->sum('c'),
            ]);

        return response()->json(['data' => [
            'window_hours' => 24,
            'completed' => (int) ($byStatus[JobRun::STATUS_COMPLETED] ?? 0),
            'failed' => (int) ($byStatus[JobRun::STATUS_FAILED] ?? 0),
            'running' => JobRun::where('status', JobRun::STATUS_RUNNING)->count(),
            'by_domain' => $byDomain,
        ]]);
    }

    public function schedule(): JsonResponse
    {
        return response()->json(['data' => ScheduleCatalog::all()]);
    }

    public function commands(): JsonResponse
    {
        return response()->json(['data' => CommandCatalog::all()]);
    }
}
