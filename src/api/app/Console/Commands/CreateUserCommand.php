<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;

class CreateUserCommand extends Command
{
    protected $signature = 'users:create
                            {email : Email to log in with}
                            {--name= : Display name (default: the part of the email before @)}
                            {--role=admin : Role to assign; must exist (RolePermissionSeeder)}
                            {--tenant= : Slug of the organization the user belongs to (tenancy on)}
                            {--super-admin : Platform super admin: no organization, every permission}';

    protected $description = 'Creates a user with a role, asking for the password without echoing it. The way to get the first admin into a production database (where DatabaseSeeder creates none) and, with tenancy, the platform super admin (--super-admin).';

    public function handle(Tenancy $tenancy): int
    {
        $tenant = null;
        if ($slug = $this->option('tenant')) {
            $tenant = Tenant::where('slug', $slug)->first();
            if (! $tenant) {
                $this->error("No organization with slug \"{$slug}\".");

                return self::FAILURE;
            }
        }
        if ($this->option('super-admin') && $tenant) {
            $this->error('A super admin belongs to no organization: drop --tenant.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $name = (string) ($this->option('name') ?: strstr($email, '@', true));
        $role = (string) $this->option('role');

        if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
            $this->error("Role \"{$role}\" doesn't exist. Run: php artisan db:seed --class=RolePermissionSeeder");

            return self::FAILURE;
        }

        $password = password('Password', required: true, hint: 'Not shown while typing.');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            ['email' => ['required', 'email', 'unique:users,email'], 'name' => ['required', 'max:255'], 'password' => [Password::defaults()]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $create = function () use ($email, $name, $password, $role) {
            $user = User::create(['email' => $email, 'name' => $name, 'password' => $password, 'email_verified_at' => now()]);
            $user->forceFill(['is_super_admin' => (bool) $this->option('super-admin')])->save();
            $user->assignRole($role);

            return $user;
        };
        $user = $tenant ? $tenancy->run($tenant, $create) : $create();

        $this->info("Created {$user->email} with role {$role}.");

        return self::SUCCESS;
    }
}
