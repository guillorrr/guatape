<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Application settings editable at runtime (as opposed to .env, which needs a
 * redeploy). Values are JSON, so they keep their type:
 *
 *   AppSetting::set('orders.auto_close_days', 30);
 *   AppSetting::get('orders.auto_close_days', 15);   // int 30
 *
 * Reads are cached forever and invalidated on write.
 */
class AppSetting extends Model
{
    private const CACHE_PREFIX = 'app_setting:';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        // Stored as [value] so a cached null is distinguishable from a miss.
        $hit = Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key) {
            $row = static::where('key', $key)->first();

            return $row ? [$row->value] : [];
        });

        return $hit === [] ? $default : $hit[0];
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX.$key);
    }
}
