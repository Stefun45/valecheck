<?php

namespace App\Services\RegistrationLookup;

use App\Models\FreeLookupLog;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Shared rate limit + usage counter for every free, unauthenticated entry
 * point to the vehicle preview lookup — the homepage widget
 * (RegistrationQuickLook) and the start-check page (StartCheck) both hit
 * the same billable One Auto call, so they share one per-IP budget rather
 * than each getting their own (which would let someone double the real
 * limit just by using both).
 */
class FreeLookupGuard
{
    private const MAX_ATTEMPTS_PER_HOUR = 10;

    public function tooManyAttempts(): bool
    {
        return RateLimiter::tooManyAttempts($this->key(), self::MAX_ATTEMPTS_PER_HOUR);
    }

    /**
     * Call once a genuine attempt is about to be made (valid format,
     * within the rate limit) — counts towards both the limit and the
     * "free lookups" usage figure shown in Admin Metrics.
     */
    public function recordAttempt(string $source): void
    {
        RateLimiter::hit($this->key(), 3600);

        FreeLookupLog::create(['source' => $source]);
    }

    private function key(): string
    {
        return 'free-vehicle-lookup:'.request()->ip();
    }
}
