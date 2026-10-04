<?php

use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetClientLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(at: fn (): array => app()->isProduction()
            ? ['^'.preg_quote((string) parse_url(config('app.url'), PHP_URL_HOST), '#').'$']
            : ['^localhost$', '^127\.0\.0\.1$'], subdomains: false);
        $middleware->alias(['staff' => EnsureStaff::class]);
        $middleware->append(SetClientLocale::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(fn (Response $response) => app(SecurityHeaders::class)->apply(request(), $response));
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
