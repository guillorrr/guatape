<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the language of the response, first match wins:
 *   1. the signed-in user's choice (users.locale)
 *   2. the browser's Accept-Language (the SPA sends its current language)
 *   3. config app.locale
 * Only codes in config app.supported_locales are accepted.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->pick($request));

        $response = $next($request);
        $response->headers->set('Content-Language', app()->getLocale());

        return $response;
    }

    private function pick(Request $request): string
    {
        $supported = config('app.supported_locales', [config('app.locale')]);

        $userLocale = $request->user()?->locale;
        if (in_array($userLocale, $supported, true)) {
            return $userLocale;
        }

        // Walk the browser's languages in preference order, matching on the
        // primary subtag ("en-US" → "en"). getPreferredLanguage() isn't used:
        // with no match it returns the first supported locale, not app.locale.
        foreach ($request->getLanguages() as $language) {
            $primary = strtolower(strtok(str_replace('_', '-', $language), '-'));
            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return config('app.locale');
    }
}
