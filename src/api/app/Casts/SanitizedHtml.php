<?php

namespace App\Casts;

use App\Support\HtmlSanitizer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * For columns edited with AppRichText: HTML is sanitized on every write, so
 * whatever path stores it (API, seeder, tinker) can't persist a script.
 *
 *   protected function casts(): array { return ['body' => SanitizedHtml::class]; }
 *
 * @implements CastsAttributes<?string, ?string>
 */
class SanitizedHtml implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return HtmlSanitizer::clean($value === null ? null : (string) $value);
    }
}
