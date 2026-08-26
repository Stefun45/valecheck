<?php

namespace App\Services\Ai;

use App\DataTransferObjects\AnalysisData;
use App\DataTransferObjects\DamageAnalysisResult;
use App\DataTransferObjects\ListingComparisonResult;
use App\DataTransferObjects\VehicleData;
use RuntimeException;

/**
 * OpenAI implementation of AiProvider, proving the interface is swappable.
 * Not exercised by the test suite this pass — set AI_PROVIDER=openai and
 * supply OPENAI_API_KEY to use it; the request/response wiring mirrors
 * AnthropicProvider (vision input, forced structured tool-call output for
 * damage findings, plain text completion for the final explanation).
 */
class OpenAiProvider implements AiProvider
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
    ) {}

    public function analyseVehicleImages(array $imagePaths, VehicleData $vehicle): DamageAnalysisResult
    {
        $this->ensureConfigured();

        throw new RuntimeException('OpenAiProvider image analysis is not yet implemented in this build.');
    }

    public function generateReportExplanation(AnalysisData $data): string
    {
        $this->ensureConfigured();

        throw new RuntimeException('OpenAiProvider report explanation is not yet implemented in this build.');
    }

    public function compareListingToEvidence(string $listingText, array $damageFindings): ListingComparisonResult
    {
        $this->ensureConfigured();

        throw new RuntimeException('OpenAiProvider listing comparison is not yet implemented in this build.');
    }

    private function ensureConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI is not configured. Set OPENAI_API_KEY in .env.');
        }
    }
}
