<?php

namespace App\Services\Valuation;

use App\DataTransferObjects\MarketValuation;
use App\DataTransferObjects\VehicleData;

/**
 * Simulated market valuation. Deterministic per vehicle so the same lookup
 * always returns the same figures. Replace with a real MarketValuationProvider
 * implementation once a live valuation data source is contracted — nothing
 * else in the codebase needs to change, callers only depend on the interface.
 */
class MockMarketValuationProvider implements MarketValuationProvider
{
    private const BASE_VALUE_NEW = [
        'BMW' => 55000,
        'Audi' => 45000,
        'Volkswagen' => 30000,
        'Ford' => 25000,
        'Vauxhall' => 20000,
    ];

    public function getValuation(VehicleData $vehicle): MarketValuation
    {
        $baseNew = self::BASE_VALUE_NEW[$vehicle->make] ?? 22000;
        $age = max(0, (int) date('Y') - (int) ($vehicle->year ?? date('Y')));

        // Roughly 18% depreciation in year one, then ~12%/year, floored at 10% of new price.
        $retail = $baseNew;
        for ($i = 0; $i < $age; $i++) {
            $retail *= $i === 0 ? 0.82 : 0.88;
        }
        $retail = max($retail, $baseNew * 0.10);

        $trade = $retail * 0.85;
        $private = $retail * 0.93;
        $clean = $retail;

        return new MarketValuation(
            cleanValue: round($clean, 2),
            tradeValue: round($trade, 2),
            retailValue: round($retail, 2),
            privateValue: round($private, 2),
            comparables: [],
            confidence: 'medium',
        );
    }
}
