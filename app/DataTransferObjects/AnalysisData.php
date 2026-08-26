<?php

namespace App\DataTransferObjects;

/**
 * Everything AiProvider::generateReportExplanation() needs to write the
 * final human-readable narrative. All financial figures inside are already
 * calculated by deterministic application code — the AI only explains them,
 * it never computes them.
 */
final readonly class AnalysisData
{
    public function __construct(
        public VehicleData $vehicle,
        public array $history,
        public array $valuation,
        public array $damageFindings,
        public array $repairEstimate,
        public array $bidRecommendation,
        public array $listingContext,
    ) {}
}
