<?php

namespace App\Services\Valuation;

use App\DataTransferObjects\MarketValuation;
use App\DataTransferObjects\VehicleData;

/**
 * Simulated market valuation, mirroring OneAutoMarketValuationProvider's
 * branch between a clean-vehicle valuation ladder and a written-off
 * vehicle's category-adjusted range — deterministic per vehicle so the
 * same lookup always returns the same figures.
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

        return $vehicle->isWrittenOff()
            ? $this->salvageValuation($retail)
            : $this->cleanValuation($retail, $baseNew);
    }

    private function cleanValuation(float $retail, float $baseNew): MarketValuation
    {
        return new MarketValuation(
            cleanValue: round($retail, 2),
            tradeValue: round($retail * 0.85, 2),
            retailValue: round($retail, 2),
            privateValue: round($retail * 0.93, 2),
            comparables: [],
            confidence: 'medium',
            source: 'ukvehicledata',
            dealerForecourt: round($retail, 2),
            tradeAverage: round($retail * 0.78, 2),
            tradePoor: round($retail * 0.65, 2),
            privateAverage: round($retail * 0.85, 2),
            partExchange: round($retail * 0.80, 2),
            auctionValue: round($retail * 0.75, 2),
            listPrice: round($baseNew, 2),
        );
    }

    private function salvageValuation(float $wouldBeCleanRetail): MarketValuation
    {
        $low = round($wouldBeCleanRetail * 0.45, 2);
        $high = round($wouldBeCleanRetail * 0.65, 2);

        return new MarketValuation(
            cleanValue: null,
            tradeValue: null,
            retailValue: null,
            privateValue: null,
            comparables: [],
            confidence: 'medium',
            source: 'salvageguide',
            categoryAdjustedLow: $low,
            categoryAdjustedHigh: $high,
            salvageAuctionBidLow: round($wouldBeCleanRetail * 0.15, 2),
            salvageAuctionBidAverage: round($wouldBeCleanRetail * 0.20, 2),
            salvageAuctionBidHigh: round($wouldBeCleanRetail * 0.28, 2),
        );
    }
}
