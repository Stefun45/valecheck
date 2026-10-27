<?php

namespace App\Services\Ai\Concerns;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The shared raw-HTTP plumbing for calling Claude's Messages API directly
 * (no SDK) — extracted from AnthropicProvider so PlateScanService can call
 * Claude the exact same way (same endpoint, headers, forced tool-use
 * pattern) without duplicating the HTTP-calling logic wholesale.
 */
trait CallsAnthropicMessagesApi
{
    private function anthropicImageBlock(string $imageContents, string $mimeType): array
    {
        return [
            'type' => 'image',
            'source' => [
                'type' => 'base64',
                'media_type' => $mimeType,
                'data' => base64_encode($imageContents),
            ],
        ];
    }

    private function callAnthropicMessages(
        ?string $apiKey,
        string $model,
        string $system,
        array $content,
        ?array $tools = null,
        ?array $toolChoice = null,
        int $maxTokens = 1024,
    ): array {
        $payload = [
            'model' => $model,
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
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Anthropic API request failed: '.$response->body());
        }

        return $response->json();
    }

    private function extractAnthropicToolInput(array $response, string $toolName): array
    {
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'tool_use' && ($block['name'] ?? null) === $toolName) {
                return $block['input'] ?? [];
            }
        }

        throw new RuntimeException("Anthropic response did not include the expected {$toolName} tool call.");
    }
}
