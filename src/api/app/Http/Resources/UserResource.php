<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Include the effective permission list (direct + via roles). Off for list
     * endpoints, where it would cost one query per row.
     */
    private bool $withPermissions = false;

    public function withPermissions(): static
    {
        $this->withPermissions = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $this->when(
                $this->withPermissions,
                fn () => $this->getAllPermissions()->pluck('name')->sort()->values(),
            ),
        ];
    }
}
