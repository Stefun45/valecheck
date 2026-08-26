<?php

namespace App\DataTransferObjects;

final readonly class PriceBreakdown
{
    public function __construct(
        public float $gross,
        public float $net,
        public float $vat,
        public float $vatRate,
        public string $currency,
    ) {}
}
