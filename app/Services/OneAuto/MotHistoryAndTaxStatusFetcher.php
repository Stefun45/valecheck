<?php

namespace App\Services\OneAuto;

use Illuminate\Support\Facades\Cache;

/**
 * The free pre-payment preview and the paid Check/Plus report both need
 * MOT history & tax status — this is the one place that fetches it, cached
 * briefly per registration, so a customer who previews then buys within
 * the window doesn't trigger a second paid API call for the same data.
 */
class MotHistoryAndTaxStatusFetcher
{
    public function __construct(private readonly OneAutoClient $client) {}

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $registration, ?int $vehicleCheckId = null): array
    {
        $vrm = self::normalise($registration);
        $minutes = (int) config('valecheck.vehicle_data.oneauto.preview_cache_minutes', 30);

        return Cache::remember(
            "oneauto:mot-tax:{$vrm}",
            now()->addMinutes($minutes),
            fn () => $this->client->get('oneauto/mothistoryandtaxstatus/v2', $registration, ['vehicle_registration_mark' => $vrm], $vehicleCheckId),
        );
    }

    public static function normalise(string $registration): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $registration));
    }
}
