<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies for Railway deployment
        $middleware->trustProxies(at: '*');

        // Enable Sanctum's stateful API for SPA authentication
        $middleware->statefulApi();

        // There is no named "login" route; the SPA handles /login
        $middleware->redirectGuestsTo('/login');

        // Register middleware aliases
        $middleware->alias([
            'company' => \App\Http\Middleware\EnsureCompanyContext::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always return JSON for API routes (e.g. 401 instead of redirecting to a missing login route)
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));
    })->create();
