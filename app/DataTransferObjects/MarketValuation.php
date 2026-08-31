<?php

namespace App\DataTransferObjects;

/**
 * Internal representation of a market valuation lookup. Two genuinely
 * different One Auto products feed this, chosen by write-off status:
 * UK Vehicle Data (clean vehicles — a full valuation ladder) or
 * SalvageGuide's bid prediction (write-off vehicles — a category-adjusted
 * range plus a salvage auction bid range). A clean vehicle never
 * populates the category/auction fields and vice versa.
 */
final readonly class MarketValuation
{
    /**
     * @param  array<int, array<string, mixed>>  $comparables
     */
    public function __construct(
        public ?float $cleanValue,
        public ?float $tradeValue,
        public ?float $retailValue,
        public ?float $privateValue,
        public array $comparables,
        public string $confidence,
        public ?string $source = null,
        // UK Vehicle Data (clean vehicles)
        public ?float $dealerForecourt = null,
        public ?float $tradeAverage = null,
        public ?float $tradePoor = null,
        public ?float $privateAverage = null,
        public ?float $partExchange = null,
        public ?float $auctionValue = null,
        public ?float $listPrice = null,
        // SalvageGuide (write-off vehicles)
        public ?float $categoryAdjustedLow = null,
        public ?float $categoryAdjustedHigh = null,
        public ?float $categoryAdjustedMidpoint = null,
        public ?float $salvageAuctionBidLow = null,
        public ?float $salvageAuctionBidAverage = null,
        public ?float $salvageAuctionBidHigh = null,
    ) {}
}
