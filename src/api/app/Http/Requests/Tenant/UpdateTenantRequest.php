<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenant)],
            'status' => ['sometimes', Rule::in([Tenant::STATUS_ACTIVE, Tenant::STATUS_SUSPENDED])],
        ];
    }
}
