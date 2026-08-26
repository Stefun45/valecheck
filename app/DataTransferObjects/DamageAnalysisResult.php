<?php

namespace App\DataTransferObjects;

/**
 * Structured result of AiProvider::analyseVehicleImages(). Named "Result"
 * (rather than the brief's literal "DamageAnalysis") to avoid colliding
 * with the App\Models\DamageAnalysis Eloquent model it gets persisted into.
 */
final readonly class DamageAnalysisResult
{
    /**
     * @param  array<int, DamageFindingData>  $findings
     */
    public function __construct(
        public string $summary,
        public string $confidence,
        public int $imagesAnalysed,
        public array $findings,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?float $estimatedCost = null,
    ) {}
}
