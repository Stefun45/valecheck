<?php

namespace App\DataTransferObjects;

final readonly class RepairItemResult
{
    public function __construct(
        public string $component,
        public string $action,
        public float $partsCost,
        public float $labourHours,
        public float $labourCost,
        public float $paintCost,
        public float $totalCost,
    ) {}
}
