<?php

namespace App\Services\Bidding;

use App\DataTransferObjects\BidRecommendationResult;

/**
 * Deterministic maximum-bid math. Never delegated to AI — the AI explains
 * these figures in the final report narrative, it does not calculate them.
 */
class MaximumBidService
{
    public function calculate(float $expectedResaleValue, float $repairCost): BidRecommendationResult
    {
        $assumptions = config('valecheck.maximum_bid');

        $auctionFees = round($expectedResaleValue * $assumptions['auction_fee_rate'], 2);
        $transportCost = (float) $assumptions['transport_cost'];
        $serviceMotAllowance = (float) $assumptions['service_mot_allowance'];
        $contingency = (float) $assumptions['contingency'];
        $requiredMargin = round($expectedResaleValue * $assumptions['required_margin_rate'], 2);

        $maximumAcquisitionPrice = round(
            $expectedResaleValue
            - $repairCost
            - $auctionFees
            - $transportCost
            - $serviceMotAllowance
            - $contingency
            - $requiredMargin,
            2
        );

        $absoluteMaximum = max(0.0, $maximumAcquisitionPrice);
        $recommendedBid = round(max(0.0, $absoluteMaximum * (1 - $assumptions['recommended_bid_discount'])), 2);

        return new BidRecommendationResult(
            expectedResaleValue: $expectedResaleValue,
            totalRepairCost: $repairCost,
            auctionFees: $auctionFees,
            transportCost: $transportCost,
            serviceMotAllowance: $serviceMotAllowance,
            contingency: $contingency,
            requiredMargin: $requiredMargin,
            maximumAcquisitionPrice: $maximumAcquisitionPrice,
            recommendedBid: $recommendedBid,
            absoluteMaximum: $absoluteMaximum,
        );
    }
}
