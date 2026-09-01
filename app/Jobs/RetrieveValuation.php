<?php

namespace App\Jobs;

use App\Models\VehicleCheck;
use App\Models\VehicleValuation;
use App\Services\Valuation\MarketValuationProvider;
use App\Services\Valuation\SalvageValuationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RetrieveValuation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $vehicleCheckId) {}

    public function handle(MarketValuationProvider $provider, SalvageValuationService $salvageService): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $check->update(['stage' => 'retrieving_valuation']);

        $vehicleData = $check->toVehicleData();
        $valuation = $provider->getValuation($vehicleData);

        if ($valuation->categoryAdjustedLow !== null) {
            // SalvageGuide returned a real market-calibrated range — use
            // the low end as the single headline figure (not the midpoint)
            // so the report gives a conservative estimate rather than one
            // that assumes the vehicle is at the better end of its
            // category/damage band. Never the flat percentage assumption
            // below.
            $salvageAdjustedValue = $valuation->categoryAdjustedLow;
            $writeOffCategoryApplied = $vehicleData->writeOffCategory;
            $discountApplied = null;
        } elseif ($vehicleData->isWrittenOff() && $valuation->cleanValue !== null) {
            // Defensive fallback only — the real/mock providers never
            // return both a clean value and a written-off vehicle
            // together, but if some future provider ever did, this keeps
            // the flat-percentage assumption as a safety net rather than
            // silently showing nothing.
            $salvage = $salvageService->valuate($valuation->cleanValue, $vehicleData->writeOffCategory);
            $salvageAdjustedValue = $salvage->adjustedValue;
            $writeOffCategoryApplied = $salvage->category;
            $discountApplied = $salvage->discountApplied;
        } else {
            $salvageAdjustedValue = null;
            $writeOffCategoryApplied = null;
            $discountApplied = null;
        }

        VehicleValuation::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'clean_value' => $valuation->cleanValue,
                'trade_value' => $valuation->tradeValue,
                'retail_value' => $valuation->retailValue,
                'private_value' => $valuation->privateValue,
                'salvage_adjusted_value' => $salvageAdjustedValue,
                'write_off_category_applied' => $writeOffCategoryApplied,
                'discount_applied' => $discountApplied,
                'comparables' => $valuation->comparables,
                'confidence' => $valuation->confidence,
                'valuation_source' => $valuation->source,
                'dealer_forecourt' => $valuation->dealerForecourt,
                'trade_average' => $valuation->tradeAverage,
                'trade_poor' => $valuation->tradePoor,
                'private_average' => $valuation->privateAverage,
                'part_exchange' => $valuation->partExchange,
                'auction_value' => $valuation->auctionValue,
                'list_price' => $valuation->listPrice,
                'category_adjusted_value_low' => $valuation->categoryAdjustedLow,
                'category_adjusted_value_high' => $valuation->categoryAdjustedHigh,
                'salvage_auction_bid_low' => $valuation->salvageAuctionBidLow,
                'salvage_auction_bid_average' => $valuation->salvageAuctionBidAverage,
                'salvage_auction_bid_high' => $valuation->salvageAuctionBidHigh,
            ]
        );
    }
}
