<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores a `?ref=` query param in the session so it survives from wherever
 * an affiliate's link lands (the homepage, a product page — not
 * necessarily /register itself) through to registration.
 */
class CaptureReferralCode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->filled('ref')) {
            session(['referral_code' => (string) $request->query('ref')]);
        }

        return $next($request);
    }
}
