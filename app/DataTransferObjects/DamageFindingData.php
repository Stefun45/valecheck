<?php

namespace App\DataTransferObjects;

final readonly class DamageFindingData
{
    public function __construct(
        public string $component,
        public string $condition,
        public ?string $severity,
        public ?string $recommendedAction,
        public float $confidence,
        public string $explanation,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            component: (string) ($data['component'] ?? 'unknown'),
            condition: (string) ($data['condition'] ?? 'unknown'),
            severity: $data['severity'] ?? null,
            recommendedAction: $data['recommended_action'] ?? null,
            confidence: (float) ($data['confidence'] ?? 0.5),
            explanation: (string) ($data['explanation'] ?? ''),
        );
    }

    public function isDamaged(): bool
    {
        return in_array($this->condition, ['damaged', 'missing'], true);
    }
}
