<?php

namespace Tests\Unit;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Keeps lang/es complete: a new framework key or a new __('…') string in app/
 * without its Spanish translation fails here instead of reaching users in English.
 */
class TranslationsTest extends TestCase
{
    private const LANG = __DIR__.'/../../lang';

    /** @return array<string, array{string}> */
    public static function langGroups(): array
    {
        return ['auth' => ['auth'], 'passwords' => ['passwords'], 'pagination' => ['pagination'], 'validation' => ['validation']];
    }

    #[DataProvider('langGroups')]
    public function test_spanish_group_has_every_english_key(string $group): void
    {
        $en = array_keys(Arr::dot(require self::LANG."/en/{$group}.php"));
        $es = array_keys(Arr::dot(require self::LANG."/es/{$group}.php"));

        // custom/attributes are per-project and may legitimately differ.
        $missing = array_filter(array_diff($en, $es), fn ($k) => ! preg_match('/^(custom|attributes)(\.|$)/', $k));

        $this->assertSame([], array_values($missing), "lang/es/{$group}.php is missing keys");
    }

    public function test_every_literal_string_in_app_has_a_spanish_translation(): void
    {
        $es = json_decode(file_get_contents(self::LANG.'/es.json'), true, flags: JSON_THROW_ON_ERROR);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../../app'));
        $missing = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // __('Sentence.') / __("Sentence.") — dotted group keys (auth.failed) live in lang/es/*.php.
            preg_match_all('/__\(\s*([\'"])(.+?)(?<!\\\\)\1/s', file_get_contents($file->getPathname()), $m);
            foreach ($m[2] as $key) {
                $key = stripslashes($key);
                if (preg_match('/^[a-z_]+\.[a-z_.]+$/', $key)) {
                    continue;
                }
                if (! array_key_exists($key, $es)) {
                    $missing[] = $key;
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'lang/es.json is missing translations');
    }
}
