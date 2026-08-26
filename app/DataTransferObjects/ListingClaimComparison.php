<?php

namespace App\DataTransferObjects;

final readonly class ListingClaimComparison
{
    public function __construct(
        public string $claim,
        public string $observation,
        public string $verdict, // supported, contradicted, inconclusive
        public string $confidence, // low, medium, high
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            claim: (string) ($data['claim'] ?? ''),
            observation: (string) ($data['valecheck_observation'] ?? ''),
            verdict: (string) ($data['verdict'] ?? 'inconclusive'),
            confidence: (string) ($data['confidence'] ?? 'low'),
        );
    }

    public function toArray(): array
    {
        return [
            'claim' => $this->claim,
            'observation' => $this->observation,
            'verdict' => $this->verdict,
            'confidence' => $this->confidence,
        ];
    }
}
