<?php

namespace App\Jobs;

use App\DataTransferObjects\BidRecommendationResult;
use App\Models\VehicleCheck;
use App\Services\Bidding\DealScoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateDealScore implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $vehicleCheckId) {}

    public function handle(DealScoreService $service): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $check->update(['stage' => 'calculating_deal_score']);

        $bidRecommendation = $check->bidRecommendation;
        $valuation = $check->valuation;
        $damageAnalysis = $check->damageAnalysis;

        $bid = new BidRecommendationResult(
            expectedResaleValue: (float) $bidRecommendation->expected_resale_value,
            totalRepairCost: (float) $bidRecommendation->total_repair_cost,
            auctionFees: (float) $bidRecommendation->auction_fees,
            transportCost: (float) $bidRecommendation->transport_cost,
            serviceMotAllowance: (float) $bidRecommendation->service_mot_allowance,
            contingency: (float) $bidRecommendation->contingency,
            requiredMargin: (float) $bidRecommendation->required_margin,
            maximumAcquisitionPrice: (float) $bidRecommendation->maximum_acquisition_price,
            recommendedBid: (float) $bidRecommendation->recommended_bid,
            absoluteMaximum: (float) $bidRecommendation->absolute_maximum,
        );

        $currentPrice = (float) ($check->current_bid ?? $check->asking_price ?? 0);
        $findings = $damageAnalysis?->findings->map->toData()->all() ?? [];

        $result = $service->score(
            currentPrice: $currentPrice,
            bid: $bid,
            damageFindings: $findings,
            marketConfidence: $valuation->confidence,
            aiConfidence: $damageAnalysis->confidence ?? 'low',
            imagesAnalysed: $damageAnalysis->images_analysed ?? 0,
        );

        $bidRecommendation->update([
            'deal_score' => $result->score,
            'recommendation' => $result->recommendation,
            'score_explanation' => $result->explanation,
        ]);
    }
}
