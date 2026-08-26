<?php

namespace App\DataTransferObjects;

final readonly class ListingComparisonResult
{
    /**
     * @param  array<int, ListingClaimComparison>  $comparisons
     */
    public function __construct(
        public array $comparisons,
    ) {}

    public function toArray(): array
    {
        return array_map(fn (ListingClaimComparison $c) => $c->toArray(), $this->comparisons);
    }
}
