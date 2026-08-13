<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block inactive / suspended accounts from authenticated surfaces.
 */
class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                abort(Response::HTTP_FORBIDDEN, 'Your account is inactive.');
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your account is inactive. Contact support.']);
        }

        return $next($request);
    }
}
