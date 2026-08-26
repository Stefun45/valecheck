<?php

namespace App\Jobs;

use App\Models\BidRecommendation;
use App\Models\VehicleCheck;
use App\Services\Bidding\MaximumBidService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateMaximumBid implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $vehicleCheckId) {}

    public function handle(MaximumBidService $service): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $check->update(['stage' => 'calculating_maximum_bid']);

        $valuation = $check->valuation;
        $repairEstimate = $check->repairEstimate;

        $expectedResaleValue = (float) ($valuation->salvage_adjusted_value ?? $valuation->clean_value ?? 0);
        $repairCost = (float) ($repairEstimate->expected_estimate ?? 0);

        $result = $service->calculate($expectedResaleValue, $repairCost);

        BidRecommendation::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'expected_resale_value' => $result->expectedResaleValue,
                'total_repair_cost' => $result->totalRepairCost,
                'auction_fees' => $result->auctionFees,
                'transport_cost' => $result->transportCost,
                'service_mot_allowance' => $result->serviceMotAllowance,
                'contingency' => $result->contingency,
                'required_margin' => $result->requiredMargin,
                'maximum_acquisition_price' => $result->maximumAcquisitionPrice,
                'recommended_bid' => $result->recommendedBid,
                'absolute_maximum' => $result->absoluteMaximum,
            ]
        );
    }
}
