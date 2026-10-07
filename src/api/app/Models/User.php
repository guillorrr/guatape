<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Tenancy\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasAttachments, HasFactory, HasRoles, Notifiable;

    /**
     * Roles are assigned through the `web` guard: the SPA authenticates with
     * the session cookie, so `auth:sanctum` resolves users through `web`.
     */
    protected string $guard_name = 'web';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /** Language of the mails and notifications sent to this user. */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }
}
