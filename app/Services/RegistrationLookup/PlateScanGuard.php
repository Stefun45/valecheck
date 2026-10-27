<?php

namespace App\Services\RegistrationLookup;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Per-IP rate limit for the mobile-web "scan number plate" camera upload
 * endpoint (PlateScanController) — public, unauthenticated, and calls a
 * paid third-party API (Claude's vision endpoint) per request, so it needs
 * the same kind of throttling as FreeLookupGuard already applies to the
 * free OneAuto DVLA preview lookup.
 *
 * Deliberately a separate budget from FreeLookupGuard rather than sharing
 * its counter: these are two different billable third-party calls (One
 * Auto DVLA data vs. Claude's vision API) for two different purposes, and
 * sharing one limit would let usage of one silently eat the other's
 * allowance and distort both.
 *
 * The threshold is higher than FreeLookupGuard's 10/hour because this is a
 * "point the camera and try again" flow rather than a single type-and-submit
 * action — a visitor may genuinely need a few attempts to get a clean,
 * well-lit, correctly-angled shot before Claude can read it. Each Haiku
 * vision call is only a fraction of a US cent, so 20/hour/IP still keeps
 * the worst-case cost of one visitor trivial while comfortably covering
 * normal retry behaviour.
 */
class PlateScanGuard
{
    private const MAX_ATTEMPTS_PER_HOUR = 20;

    public function tooManyAttempts(): bool
    {
        return RateLimiter::tooManyAttempts($this->key(), self::MAX_ATTEMPTS_PER_HOUR);
    }

    public function recordAttempt(): void
    {
        RateLimiter::hit($this->key(), 3600);
    }

    private function key(): string
    {
        return 'plate-scan:'.request()->ip();
    }
}
