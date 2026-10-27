<?php

namespace App\Services\Ai;

use App\Models\AiUsage;
use App\Services\Ai\Concerns\CallsAnthropicMessagesApi;
use Throwable;

/**
 * Reads a UK number plate from a freshly-uploaded photo on the mobile
 * website, via Claude's vision capability — the web equivalent of the
 * native app's on-device camera scanner (see UKPlateMatcher.swift).
 *
 * Deliberately always calls Claude directly (not the swappable AiProvider
 * used for Rebuild's damage analysis), and always on a cheap/fast
 * Haiku-class model (config('valecheck.ai.plate_scan_model')) — reading a
 * handful of characters off a plate is a trivial transcription task, not
 * reasoning, and this is a publicly-reachable, unauthenticated endpoint
 * where speed and cost both matter.
 *
 * This is an additive convenience on top of manual entry, which always
 * keeps working regardless of this feature: any failure (missing API key,
 * network/API error, no legible plate, or a result that fails UK plate
 * format validation) degrades to null rather than throwing, so it never
 * breaks the page it's called from. Every attempt — success or failure —
 * is still logged to AiUsage so its cost stays visible, the same as every
 * other AI call in the app.
 */
class PlateScanService
{
    use CallsAnthropicMessagesApi;

    private const PROVIDER = 'anthropic';

    private const STAGE = 'plate_scan';

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
    ) {}

    /**
     * @param  string  $imageContents  Raw bytes of the uploaded photo — never written to disk.
     */
    public function scan(string $imageContents, string $mimeType): ?string
    {
        $startedAt = microtime(true);

        if (empty($this->apiKey)) {
            $this->logAttempt($startedAt, success: false, errorMessage: 'Anthropic is not configured. Set ANTHROPIC_API_KEY in .env.');

            return null;
        }

        try {
            $response = $this->callAnthropicMessages(
                apiKey: $this->apiKey,
                model: $this->model,
                system: $this->systemPrompt(),
                content: [
                    $this->anthropicImageBlock($imageContents, $mimeType),
                    ['type' => 'text', 'text' => 'Read the number plate in this photograph and report it using the tool.'],
                ],
                tools: [$this->tool()],
                toolChoice: ['type' => 'tool', 'name' => 'report_number_plate'],
                maxTokens: 64,
            );

            $input = $this->extractAnthropicToolInput($response, 'report_number_plate');
            $usage = $response['usage'] ?? [];
            $plate = $input['plate'] ?? null;

            $this->logAttempt(
                $startedAt,
                success: true,
                inputTokens: $usage['input_tokens'] ?? null,
                outputTokens: $usage['output_tokens'] ?? null,
                estimatedCost: $this->estimateCost($usage),
            );

            return (is_string($plate) && $plate !== '') ? $plate : null;
        } catch (Throwable $e) {
            $this->logAttempt($startedAt, success: false, errorMessage: $e->getMessage());

            return null;
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
            You read UK vehicle registration plates from photographs for ValeCheck, so a website
            visitor doesn't have to type their plate in by hand.

            Look at the supplied photograph and report the registration plate text exactly as
            printed on the plate (letters and digits only, as shown).

            Rules:
            - Only report a plate you can actually read clearly in the photograph.
            - If no plate is visible, or it's too blurry/obscured/angled to read confidently, report null — never guess.
            - Use the report_number_plate tool to return your answer. Do not respond in free text.
            PROMPT;
    }

    private function tool(): array
    {
        return [
            'name' => 'report_number_plate',
            'description' => 'Report the vehicle registration plate text read from the supplied photograph.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'plate' => [
                        'type' => ['string', 'null'],
                        'description' => 'The vehicle registration plate text exactly as shown, or null if no plate is clearly visible/legible.',
                    ],
                ],
                'required' => ['plate'],
            ],
        ];
    }

    private function logAttempt(
        float $startedAt,
        bool $success,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?float $estimatedCost = null,
        ?string $errorMessage = null,
    ): void {
        AiUsage::create([
            'provider' => self::PROVIDER,
            'model' => $this->model,
            'stage' => self::STAGE,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'image_count' => 1,
            'estimated_cost' => $estimatedCost,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'success' => $success,
            'error_message' => $errorMessage,
        ]);
    }

    private function estimateCost(array $usage): ?float
    {
        if (! isset($usage['input_tokens'], $usage['output_tokens'])) {
            return null;
        }

        // Approximate Claude Haiku pricing per 1M tokens: $1 in / $5 out.
        $inputCost = ($usage['input_tokens'] / 1_000_000) * 1;
        $outputCost = ($usage['output_tokens'] / 1_000_000) * 5;

        return round($inputCost + $outputCost, 4);
    }
}
