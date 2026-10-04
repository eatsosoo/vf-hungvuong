<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClientLocale
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->is('en', 'en/*') ? 'en' : 'vi';
        app()->setLocale($locale);
        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
