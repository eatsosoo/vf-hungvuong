<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction() && ! $request->isSecure()) {
            abort(403, 'Website production yêu cầu HTTPS.');
        }

        return $this->apply($request, $next($request));
    }

    public function apply(Request $request, Response $response): Response
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'", "script-src 'self'", "style-src 'self'",
            "img-src 'self' data: https:", "font-src 'self'", "connect-src 'self'",
            'frame-src https://www.youtube-nocookie.com',
            "object-src 'none'", "base-uri 'self'", "frame-ancestors 'none'", "form-action 'self'",
        ]));
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        if ($request->is('admin*') || $request->is('dang-nhap')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
