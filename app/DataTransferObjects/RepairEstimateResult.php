<?php

namespace App\DataTransferObjects;

final readonly class RepairEstimateResult
{
    /**
     * @param  array<int, RepairItemResult>  $items
     */
    public function __construct(
        public float $lowEstimate,
        public float $expectedEstimate,
        public float $highEstimate,
        public array $items,
        public array $assumptionsSnapshot,
    ) {}
}
