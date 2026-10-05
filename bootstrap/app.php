<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        // Behind Cloudflare Tunnel: trust the proxy so https URLs and
        // secure cookies are generated correctly.
        $middleware->trustProxies(at: '*');
        // Pub/Sub posts here from outside; it has no CSRF token.
        $middleware->validateCsrfTokens(except: ['gmail/webhook/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
