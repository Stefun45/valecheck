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
        $salvage = $salvageService->valuate($valuation->cleanValue ?? 0.0, $vehicleData->writeOffCategory);

        VehicleValuation::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'clean_value' => $valuation->cleanValue,
                'trade_value' => $valuation->tradeValue,
                'retail_value' => $valuation->retailValue,
                'private_value' => $valuation->privateValue,
                'salvage_adjusted_value' => $salvage->adjustedValue,
                'write_off_category_applied' => $salvage->category,
                'discount_applied' => $salvage->discountApplied,
                'comparables' => $valuation->comparables,
                'confidence' => $valuation->confidence,
            ]
        );
    }
}
