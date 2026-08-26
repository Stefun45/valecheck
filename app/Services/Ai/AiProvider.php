<?php

namespace App\Services\Ai;

use App\DataTransferObjects\AnalysisData;
use App\DataTransferObjects\DamageAnalysisResult;
use App\DataTransferObjects\ListingComparisonResult;
use App\DataTransferObjects\VehicleData;

interface AiProvider
{
    /**
     * @param  array<int, string>  $imagePaths  Absolute local filesystem paths to the images.
     */
    public function analyseVehicleImages(array $imagePaths, VehicleData $vehicle): DamageAnalysisResult;

    public function generateReportExplanation(AnalysisData $data): string;

    /**
     * Contrasts what the seller's listing text claims against the evidence
     * already found in the photographs, for the "Listing vs Evidence"
     * report section. Must never present an inconclusive observation as a
     * stated fact.
     *
     * @param  array<int, array<string, mixed>>  $damageFindings
     */
    public function compareListingToEvidence(string $listingText, array $damageFindings): ListingComparisonResult;
}
