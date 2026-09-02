<?php

use App\Http\Middleware\CaptureReferralCode;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RedirectGuestsToComingSoon;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust the platform's own load balancer (Laravel Cloud) so
        // $request->ip() resolves to the real visitor IP from
        // X-Forwarded-For rather than the load balancer's own address.
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->web(prepend: [
            RedirectGuestsToComingSoon::class,
        ]);

        $middleware->web(append: [
            CaptureReferralCode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
