<?php

namespace App\DataTransferObjects;

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
    ) {}
}
