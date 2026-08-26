<?php

namespace App\DataTransferObjects;

final readonly class SalvageValuation
{
    public function __construct(
        public float $cleanValue,
        public ?string $category,
        public ?float $discountApplied,
        public ?float $adjustedValue,
        public string $note,
    ) {}
}
