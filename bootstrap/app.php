<?php

use App\Http\Middleware\CaptureReferralCode;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RedirectGuestsToComingSoon;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

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

        // Appended, not prepended — this needs auth()->check() to work,
        // which needs the session to already be loaded. Prepending would
        // run it before the web group's own session-starting middleware,
        // so it would see every request as a guest, logged in or not.
        $middleware->web(append: [
            RedirectGuestsToComingSoon::class,
            CaptureReferralCode::class,
        ]);

        // Array order within the web group above isn't the whole story —
        // Laravel's framework middleware (auth, verified, etc.) has a
        // fixed priority that overrides plain array position whenever a
        // route mixes global and route-specific middleware. Without this,
        // a guest hitting an auth-protected route (e.g. /reports) got
        // Laravel's own auth redirect (to /login) instead of ours (to the
        // coming-soon page), because Authenticate's priority put it ahead
        // of ours regardless of array order. This pins the gate to run
        // right after the session starts but before Authenticate does.
        $middleware->appendToPriorityList(
            after: StartSession::class,
            append: RedirectGuestsToComingSoon::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
