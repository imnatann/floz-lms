<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.session' => \App\Http\Middleware\ResolveTenantFromSession::class,
            'platform.access' => \App\Http\Middleware\PlatformAccess::class,
            'tenant.access' => \App\Http\Middleware\TenantAccess::class,
        ]);
        
        $middleware->web(append: [
            \App\Http\Middleware\IdentifyTenant::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Ensure IdentifyTenant runs BEFORE auth middleware
        // Laravel's middleware priority system sorts route-level middleware
        // relative to group middleware. Without this, Authenticate runs before
        // IdentifyTenant, meaning auth check happens without tenant DB setup.
        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\IdentifyTenantFromHeader::class,        // ← BEFORE auth (Mobile)
            \App\Http\Middleware\IdentifyTenant::class,                  // ← BEFORE auth (Web)
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Validation Failed (Mobile Login): ' . json_encode($e->errors()));
        });
    })->create();
