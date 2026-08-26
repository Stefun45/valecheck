<?php

namespace App\DataTransferObjects;

final readonly class DealScoreResult
{
    public function __construct(
        public int $score,
        public string $recommendation,
        public string $explanation,
        public array $breakdown,
    ) {}
}
