<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Persist the visitor's preferred UI language.
 */
class LocaleController extends Controller
{
    /**
     * Switch application locale and redirect back.
     */
    public function update(Request $request): RedirectResponse
    {
        $locales = array_keys(config('cyra.locales', ['en' => 'English']));

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($locales)],
        ]);

        $locale = $validated['locale'];

        $request->session()->put(SetLocale::SESSION_KEY, $locale);
        app()->setLocale($locale);

        $fallback = $request->user()
            ? route('dashboard')
            : route('home');

        $previous = url()->previous();
        $target = ($previous && $previous !== url()->current())
            ? $previous
            : $fallback;

        return redirect()
            ->to($target)
            ->with('status', __('ui.language_updated', [
                'language' => config('cyra.locales.'.$locale, $locale),
            ]))
            ->withCookie(cookie(
                SetLocale::COOKIE_KEY,
                $locale,
                60 * 24 * 365,
                '/',
                null,
                (bool) config('session.secure'),
                false,
                false,
                'lax'
            ));
    }
}
