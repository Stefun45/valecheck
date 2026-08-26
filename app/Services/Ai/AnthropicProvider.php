<?php

namespace App\Services\Ai;

use App\DataTransferObjects\AnalysisData;
use App\DataTransferObjects\DamageAnalysisResult;
use App\DataTransferObjects\DamageFindingData;
use App\DataTransferObjects\ListingClaimComparison;
use App\DataTransferObjects\ListingComparisonResult;
use App\DataTransferObjects\VehicleData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicProvider implements AiProvider
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
    ) {}

    public function analyseVehicleImages(array $imagePaths, VehicleData $vehicle): DamageAnalysisResult
    {
        $this->ensureConfigured();

        $imageBlocks = $this->buildImageBlocks($imagePaths);

        $systemPrompt = <<<'PROMPT'
            You are a vehicle inspection assistant for ValeCheck, a UK vehicle buying-decision platform.
            Examine the supplied photographs of a vehicle and report what you can actually see.

            Inspect (where visible): front/rear bumpers, wings, doors, bonnet, boot, roof, headlights,
            tail lights, glass, wheels, tyres, suspension clues, visible steering components, radiators,
            cooling system, condensers, intercoolers, airbags, seatbelts, and any structural, mechanical,
            flood, fire or previous-repair indicators.

            Rules:
            - Only report damage you can actually see in the images. Never guess at damage that isn't visible.
            - Clearly separate VISIBLE damage from anything that is merely POSSIBLE/HIDDEN and cannot be confirmed from photos.
            - You must never claim that photographs can reliably identify concealed mechanical, electrical or structural damage.
            - If a component isn't shown in any photo, don't report on it.
            - Use the report_damage_findings tool to return your findings. Do not respond in free text.
            PROMPT;

        $userText = sprintf(
            'Vehicle: %s. Analyse the attached photographs and report findings using the tool.',
            $vehicle->description() ?: $vehicle->registration
        );

        $tool = [
            'name' => 'report_damage_findings',
            'description' => 'Report structured, evidence-based damage findings from the supplied vehicle photographs.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'summary' => ['type' => 'string', 'description' => 'Brief overall summary of visible condition.'],
                    'confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                    'findings' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'component' => ['type' => 'string'],
                                'condition' => ['type' => 'string', 'enum' => ['ok', 'damaged', 'missing', 'unknown']],
                                'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                                'recommended_action' => ['type' => 'string', 'enum' => ['none', 'repair', 'replace', 'inspect']],
                                'confidence' => ['type' => 'number'],
                                'explanation' => ['type' => 'string'],
                            ],
                            'required' => ['component', 'condition', 'confidence', 'explanation'],
                        ],
                    ],
                ],
                'required' => ['summary', 'confidence', 'findings'],
            ],
        ];

        $response = $this->callMessages(
            system: $systemPrompt,
            content: [...$imageBlocks, ['type' => 'text', 'text' => $userText]],
            tools: [$tool],
            toolChoice: ['type' => 'tool', 'name' => 'report_damage_findings'],
            maxTokens: 2048,
        );

        $input = $this->extractToolInput($response, 'report_damage_findings');

        $findings = array_map(
            fn (array $finding) => DamageFindingData::fromArray($finding),
            $input['findings'] ?? []
        );

        $usage = $response['usage'] ?? [];

        return new DamageAnalysisResult(
            summary: (string) ($input['summary'] ?? ''),
            confidence: (string) ($input['confidence'] ?? 'medium'),
            imagesAnalysed: count($imagePaths),
            findings: $findings,
            inputTokens: $usage['input_tokens'] ?? null,
            outputTokens: $usage['output_tokens'] ?? null,
            estimatedCost: $this->estimateCost($usage),
        );
    }

    public function generateReportExplanation(AnalysisData $data): string
    {
        $this->ensureConfigured();

        $systemPrompt = <<<'PROMPT'
            You are writing the final human-readable summary for a ValeCheck Rebuild vehicle report.
            All financial figures (repair cost, valuation, maximum bid, deal score) have already been
            calculated by deterministic application logic and are supplied to you as facts — do not
            recalculate, second-guess or alter any numbers. Your job is only to explain them plainly for
            a car buyer who is not a mechanic or a data analyst. Be direct and specific. Keep it concise.
            PROMPT;

        $userText = "Vehicle data:\n".json_encode([
            'vehicle' => $data->vehicle,
            'history' => $data->history,
            'valuation' => $data->valuation,
            'damage_findings' => $data->damageFindings,
            'repair_estimate' => $data->repairEstimate,
            'bid_recommendation' => $data->bidRecommendation,
            'listing_context' => $data->listingContext,
        ], JSON_PRETTY_PRINT);

        $response = $this->callMessages(
            system: $systemPrompt,
            content: [['type' => 'text', 'text' => $userText]],
            maxTokens: 1024,
        );

        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                return trim($block['text']);
            }
        }

        return '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $damageFindings
     */
    public function compareListingToEvidence(string $listingText, array $damageFindings): ListingComparisonResult
    {
        $this->ensureConfigured();

        $systemPrompt = <<<'PROMPT'
            You compare a seller's vehicle listing description against damage
            findings already extracted from photographs, for ValeCheck's
            "Listing vs Evidence" report section.

            Rules:
            - For each distinct factual claim the seller makes (condition, damage, mileage, "runs and drives", etc.), state ValeCheck's actual photographic observation relevant to that claim.
            - Mark a comparison "supported" only if the photographic evidence positively confirms the claim, "contradicted" only if the evidence clearly conflicts with it, and "inconclusive" whenever photographs cannot verify the claim either way — which is common for mechanical, electrical or structural claims.
            - Never state an inconclusive observation as if it were a confirmed fact. Phrase inconclusive findings as an inability to confirm, not as a positive or negative claim.
            - Only compare claims actually present in the listing text. Do not invent seller claims.
            - Use the report_listing_comparison tool to return your findings. Do not respond in free text.
            PROMPT;

        $userText = "Seller's listing text:\n{$listingText}\n\nPhotographic damage findings:\n"
            .json_encode($damageFindings, JSON_PRETTY_PRINT);

        $tool = [
            'name' => 'report_listing_comparison',
            'description' => 'Report a structured comparison of seller listing claims against photographic evidence.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'comparisons' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'claim' => ['type' => 'string'],
                                'valecheck_observation' => ['type' => 'string'],
                                'verdict' => ['type' => 'string', 'enum' => ['supported', 'contradicted', 'inconclusive']],
                                'confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                            ],
                            'required' => ['claim', 'valecheck_observation', 'verdict', 'confidence'],
                        ],
                    ],
                ],
                'required' => ['comparisons'],
            ],
        ];

        $response = $this->callMessages(
            system: $systemPrompt,
            content: [['type' => 'text', 'text' => $userText]],
            tools: [$tool],
            toolChoice: ['type' => 'tool', 'name' => 'report_listing_comparison'],
            maxTokens: 1536,
        );

        $input = $this->extractToolInput($response, 'report_listing_comparison');

        $comparisons = array_map(
            fn (array $c) => ListingClaimComparison::fromArray($c),
            $input['comparisons'] ?? []
        );

        return new ListingComparisonResult($comparisons);
    }

    private function ensureConfigured(): void
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Anthropic is not configured. Set ANTHROPIC_API_KEY in .env.');
        }
    }

    /**
     * @param  array<int, string>  $imagePaths
     * @return array<int, array<string, mixed>>
     */
    private function buildImageBlocks(array $imagePaths): array
    {
        return array_map(function (string $path) {
            $mimeType = mime_content_type($path) ?: 'image/jpeg';
            $data = base64_encode(file_get_contents($path));

            return [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $mimeType,
                    'data' => $data,
                ],
            ];
        }, $imagePaths);
    }

    private function callMessages(string $system, array $content, ?array $tools = null, ?array $toolChoice = null, int $maxTokens = 1024): array
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $content],
            ],
        ];

        if ($tools) {
            $payload['tools'] = $tools;
        }

        if ($toolChoice) {
            $payload['tool_choice'] = $toolChoice;
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])->post(self::API_URL, $payload);

        if ($response->failed()) {
            throw new RuntimeException('Anthropic API request failed: '.$response->body());
        }

        return $response->json();
    }

    private function extractToolInput(array $response, string $toolName): array
    {
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'tool_use' && ($block['name'] ?? null) === $toolName) {
                return $block['input'] ?? [];
            }
        }

        throw new RuntimeException("Anthropic response did not include the expected {$toolName} tool call.");
    }

    private function estimateCost(array $usage): ?float
    {
        if (! isset($usage['input_tokens'], $usage['output_tokens'])) {
            return null;
        }

        // Approximate Claude Sonnet pricing per 1M tokens: $3 in / $15 out.
        $inputCost = ($usage['input_tokens'] / 1_000_000) * 3;
        $outputCost = ($usage['output_tokens'] / 1_000_000) * 15;

        return round($inputCost + $outputCost, 4);
    }
}
