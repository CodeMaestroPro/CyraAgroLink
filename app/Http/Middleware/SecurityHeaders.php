<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply defensive HTTP security headers to every response.
 */
class SecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // Baseline CSP — allow app assets, Bunny fonts, Vite in local, YouTube embeds if needed.
        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "img-src 'self' data: blob: https:",
            "font-src 'self' https://fonts.bunny.net data:",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "script-src 'self' 'unsafe-inline'".($this->isLocal() ? " 'unsafe-eval'" : ''),
            "connect-src 'self'".($this->isLocal() ? ' ws: wss: http: https:' : ''),
            "frame-src 'self' https://www.youtube.com https://youtube.com",
            'upgrade-insecure-requests',
        ]);

        if (! $this->isLocal()) {
            $response->headers->set('Content-Security-Policy', $csp);
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        } else {
            // Report-only in local so Vite/dev tooling is not blocked while still validating policy.
            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
        }

        return $response;
    }

    protected function isLocal(): bool
    {
        return app()->environment(['local', 'testing']);
    }
}
