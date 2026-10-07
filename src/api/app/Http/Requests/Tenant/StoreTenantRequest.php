<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', 'unique:tenants,slug', Rule::notIn(config('tenancy.reserved_slugs', []))],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'status' => ['sometimes', Rule::in([Tenant::STATUS_ACTIVE, Tenant::STATUS_SUSPENDED])],
            // The organization's first admin.
            'admin.name' => ['required', 'string', 'max:255'],
            'admin.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin.password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
