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
        // Livewire's own transport (component updates, file uploads, its
        // JS assets) — not a page a guest can navigate to, but every
        // Livewire component action (including the login button itself)
        // is an AJAX call to livewire/update. Blocking it meant the login
        // form's submission never reached Auth::attempt() at all: it was
        // redirected before the component's own login() method ever ran,
        // so no session was ever created and the browser just followed
        // the redirect straight back to the coming-soon page. This is
        // safe to exempt broadly — a guest still can't act on a
        // protected component this way, since Livewire's own signed
        // snapshot check rejects any update for a component they were
        // never able to mount in the first place.
        'livewire/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('valecheck.coming_soon_enabled') || auth()->check() || $request->is(...self::EXEMPT_PATHS) || $request->is('/')) {
            return $next($request);
        }

        return redirect()->route('welcome');
    }
}
