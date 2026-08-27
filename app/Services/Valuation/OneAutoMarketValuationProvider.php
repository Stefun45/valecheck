<?php

namespace App\Services\Valuation;

use App\DataTransferObjects\MarketValuation;
use App\DataTransferObjects\VehicleData;
use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\OneAuto\OneAutoApiException;
use App\Services\OneAuto\OneAutoClient;

/**
 * Brego "Current Valuation (UK) from VRM" — confirmed against the One Auto
 * pricing table as the "(UK)" one, distinct from Cazana/Percayso's
 * identically-named product. Needs current_mileage, which isn't collected
 * from the customer any more (removed from the Plus form as unused before
 * this migration) — derived instead from the most recent MOT test's
 * odometer reading. A missing/unavailable valuation degrades this section
 * to "unavailable" rather than failing the whole Plus report, since
 * valuation is additive on top of the safety-critical provenance check.
 */
class OneAutoMarketValuationProvider implements MarketValuationProvider
{
    private const ENDPOINT = 'brego/valuationfromvrm/v2';

    public function __construct(private readonly OneAutoClient $client) {}

    public function getValuation(VehicleData $vehicle): MarketValuation
    {
        $mileage = $this->latestMileage($vehicle);

        if ($mileage === null) {
            return $this->unavailable();
        }

        try {
            $result = $this->client->get(self::ENDPOINT, $vehicle->registration, [
                'vehicle_registration_mark' => MotHistoryAndTaxStatusFetcher::normalise($vehicle->registration),
                'current_mileage' => $mileage,
            ]);
        } catch (OneAutoApiException) {
            return $this->unavailable();
        }

        if (! isset($result['retail_average_valuation'])) {
            return $this->unavailable();
        }

        return new MarketValuation(
            cleanValue: $result['retail_average_valuation'],
            tradeValue: $result['trade_average_valuation'] ?? null,
            retailValue: $result['retail_high_valuation'] ?? null,
            privateValue: null, // Brego doesn't return a separate private-sale figure.
            comparables: [],
            confidence: 'medium',
        );
    }

    private function unavailable(): MarketValuation
    {
        return new MarketValuation(null, null, null, null, [], 'unavailable');
    }

    private function latestMileage(VehicleData $vehicle): ?int
    {
        $latest = collect($vehicle->motHistory)
            ->filter(fn (array $test) => isset($test['mileage'], $test['test_date']))
            ->sortByDesc('test_date')
            ->first();

        return $latest['mileage'] ?? null;
    }
}
