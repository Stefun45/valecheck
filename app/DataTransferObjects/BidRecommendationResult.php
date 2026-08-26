<?php

namespace App\DataTransferObjects;

final readonly class BidRecommendationResult
{
    public function __construct(
        public float $expectedResaleValue,
        public float $totalRepairCost,
        public float $auctionFees,
        public float $transportCost,
        public float $serviceMotAllowance,
        public float $contingency,
        public float $requiredMargin,
        public float $maximumAcquisitionPrice,
        public float $recommendedBid,
        public float $absoluteMaximum,
    ) {}
}
