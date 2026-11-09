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
        // Expired/mismatched CSRF token (page left open too long, double tap on
        // Sign in, back button…): never show the bare 419 page — go back to the
        // form with what was typed and a friendly message.
        // (Laravel turns TokenMismatchException into an HttpException 419 before these callbacks run.)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session expired. Please refresh the page and try again.'], 419);
            }
            if (auth()->check() && $request->is('login')) {
                return redirect()->route('inbox');          // first tap already signed in
            }
            if ($request->is('login') || $request->is('logout') || !auth()->check()) {
                return redirect('/login')
                    ->withInput($request->only('username', 'portal'))
                    ->withErrors(['username' => 'Your session expired. Please sign in again.']);
            }
            $back = url()->previous();
            return redirect($back && $back !== $request->fullUrl() ? $back : route('inbox'))
                ->withInput($request->except('_token', 'password', 'password_confirmation', 'current_password'))
                ->withErrors(['session' => 'Your session expired, so nothing was saved. Please try again.']);
        });
    })->create();
