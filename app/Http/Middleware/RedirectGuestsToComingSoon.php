<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pre-launch gate, replacing the old HTTP Basic Auth site password: a
 * logged-out visitor hitting anything other than an always-exempt path is
 * sent to the homepage, which shows a "coming soon" page instead of the
 * real marketing site when there's no authenticated user (see the `/`
 * route in routes/web.php). A logged-in user passes straight through to
 * whatever they requested, exactly as before this gate existed.
 *
 * Registration is deliberately not exempt — self-signup is closed while
 * this gate is active, so the only way in is an existing account (see
 * demo:setup-experian-account for creating one for an external reviewer).
 */
class RedirectGuestsToComingSoon
{
    private const EXEMPT_PATHS = [
        'stripe/webhook', 'up', 'terms', 'privacy',
        'login', 'forgot-password', 'reset-password/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('valecheck.coming_soon_enabled') || auth()->check() || $request->is(...self::EXEMPT_PATHS) || $request->is('/')) {
            return $next($request);
        }

        return redirect()->route('welcome');
    }
}
