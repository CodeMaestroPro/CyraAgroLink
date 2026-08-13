<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to users possessing one of the allowed roles.
 *
 * Platform administrators may access any role-gated route.
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $userRole = $user->role instanceof \BackedEnum
            ? $user->role->value
            : (string) $user->role;

        if (! in_array($userRole, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'You are not authorized to perform this action.');
        }

        return $next($request);
    }
}
