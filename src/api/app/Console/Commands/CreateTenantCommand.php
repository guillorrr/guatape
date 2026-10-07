<?php

namespace App\Console\Commands;

use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenants:create
                            {slug : Subdomain of the organization (<slug>.<central domain>)}
                            {name : Display name}
                            {--domain= : Custom domain, if it has one}
                            {--admin-email= : Email of its first admin (required)}
                            {--admin-name= : Name of its first admin (default: the part before @)}';

    protected $description = 'Creates an organization (tenant) with its first admin, asking for the admin password without echoing it. Needs TENANCY_ENABLED=true.';

    public function handle(TenantService $service): int
    {
        if (! config('tenancy.enabled')) {
            $this->error('Tenancy is disabled (TENANCY_ENABLED=false).');

            return self::FAILURE;
        }

        $email = (string) $this->option('admin-email');
        $data = [
            'name' => (string) $this->argument('name'),
            'slug' => (string) $this->argument('slug'),
            'domain' => $this->option('domain') ?: null,
            'admin' => [
                'name' => (string) ($this->option('admin-name') ?: strstr($email, '@', true)),
                'email' => $email,
            ],
        ];
        $data['admin']['password'] = $data['admin']['password_confirmation'] = password('Admin password', required: true);

        $validator = Validator::make($data, (new StoreTenantRequest)->rules());
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $tenant = $service->create(
            collect($data)->except('admin')->all(),
            collect($data['admin'])->only('name', 'email', 'password')->all(),
        );

        $this->info("Created {$tenant->name}: {$tenant->url()} (admin {$email}).");

        return self::SUCCESS;
    }
}
