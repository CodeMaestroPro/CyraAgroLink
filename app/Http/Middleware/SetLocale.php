<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply the user's preferred UI locale from session or cookie.
 */
class SetLocale
{
    public const SESSION_KEY = 'locale';

    public const COOKIE_KEY = 'cyra_locale';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('cyra.locales', ['en' => 'English']));
        $fallback = (string) config('app.locale', 'en');

        $locale = $request->session()->get(self::SESSION_KEY)
            ?? $request->cookie(self::COOKIE_KEY)
            ?? $fallback;

        if (! in_array($locale, $available, true)) {
            $locale = $fallback;
        }

        app()->setLocale($locale);
        $request->session()->put(self::SESSION_KEY, $locale);

        return $next($request);
    }
}
