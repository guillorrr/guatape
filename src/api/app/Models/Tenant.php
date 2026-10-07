<?php

namespace App\Models;

use App\Tenancy\TenantScope;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * An organization using the app. Resolved per request by IdentifyTenant from
 * "<slug>.<central domain>" or from `domain` (a custom domain).
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = ['name', 'slug', 'domain', 'status', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    /** All the organization's users, whatever context the code runs in. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class)->withoutGlobalScope(TenantScope::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** A tenant-level setting (dot notation), e.g. tenant()->setting('branding.color'). */
    public function setting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings ?? [], $key, $default);
    }

    /** Public URL of the tenant's app: its own domain, or <slug>.<first central domain>. */
    public function url(): string
    {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $port = parse_url((string) config('app.url'), PHP_URL_PORT);
        $host = $this->domain ?: $this->slug.'.'.(config('tenancy.central_domains')[0] ?? 'localhost');

        return $scheme.'://'.$host.($port ? ':'.$port : '');
    }
}
