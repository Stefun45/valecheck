<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Temporary pre-launch gate — when valecheck.site_password is set, every
 * request must authenticate via HTTP Basic Auth (browser-native prompt, no
 * login page needed) before reaching the site. Clearing the env var is a
 * complete no-op, same reversible-flag pattern as rebuild_enabled/
 * subscriptions_enabled — remove it to go fully live with no code changes.
 *
 * Always excludes the Stripe webhook (Stripe's servers, not a browser,
 * hit it — they can't answer a Basic Auth prompt) and the health-check
 * route (Laravel Cloud polls this to know the app is alive).
 */
class RequireSitePassword
{
    private const EXEMPT_PATHS = ['stripe/webhook', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        $password = config('valecheck.site_password');

        if (empty($password) || $request->is(...self::EXEMPT_PATHS)) {
            return $next($request);
        }

        if ($request->getPassword() === $password) {
            return $next($request);
        }

        return response('Unauthorized', 401, ['WWW-Authenticate' => 'Basic realm="ValeCheck"']);
    }
}
