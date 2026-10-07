<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Tenancy\Tenancy;
use Illuminate\Http\JsonResponse;

/**
 * Public: tells the SPA which context it runs in, before sign-in (branding,
 * whether to show the platform admin). With tenancy off: {enabled: false}.
 */
class TenancyController extends Controller
{
    public function __invoke(Tenancy $tenancy): JsonResponse
    {
        $tenant = $tenancy->current();

        return response()->json(['data' => [
            'enabled' => $tenancy->enabled(),
            'central' => $tenancy->isCentral(),
            'tenant' => $tenant ? ['id' => $tenant->id, 'name' => $tenant->name, 'slug' => $tenant->slug] : null,
        ]]);
    }
}
