<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Platform administration of organizations (super admins, central domain).
 * Suspending is the way to turn one off: there's no delete endpoint, removing
 * an organization's data is a deliberate, offline operation.
 */
class TenantController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenants = ListQuery::for(Tenant::query()->withCount('users'), $request)
            ->search(['name', 'slug', 'domain'])
            ->sortable(['name', 'slug', 'created_at'], default: 'name')
            ->filter('status', fn ($q, string $status) => $q->where('status', $status))
            ->paginate();

        return TenantResource::collection($tenants);
    }

    public function store(StoreTenantRequest $request, TenantService $service): JsonResponse
    {
        $data = $request->validated();
        $tenant = $service->create(collect($data)->except('admin')->all(), collect($data['admin'])->only('name', 'email', 'password')->all());

        return (new TenantResource($tenant->loadCount('users')))->response()->setStatusCode(201);
    }

    public function show(Tenant $tenant): TenantResource
    {
        return new TenantResource($tenant->loadCount('users'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): TenantResource
    {
        $tenant->update($request->validated());

        return new TenantResource($tenant->loadCount('users'));
    }
}
