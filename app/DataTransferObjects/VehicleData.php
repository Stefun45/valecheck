<?php

namespace App\DataTransferObjects;

/**
 * Internal representation of a vehicle lookup. Every VehicleDataProvider
 * (mock or real) must translate its own response shape into this DTO —
 * application code never touches a raw provider payload directly.
 */
final readonly class VehicleData
{
    /**
     * @param  array<int, array<string, mixed>>  $motHistory
     * @param  array<int, array<string, mixed>>  $keeperHistory
     */
    public function __construct(
        public string $registration,
        public ?string $vin,
        public ?string $make,
        public ?string $model,
        public ?string $derivative,
        public ?int $year,
        public ?string $engine,
        public ?string $fuel,
        public ?string $transmission,
        public ?string $colour,
        public ?string $specification,
        public ?string $writeOffCategory,
        public ?string $writeOffDate,
        public bool $financeMarker,
        public bool $stolenMarker,
        public bool $scrappedMarker,
        public bool $imported,
        public bool $exported,
        public ?int $previousKeepers,
        public ?int $plateChanges,
        public bool $mileageAnomaly,
        public array $motHistory,
        public array $keeperHistory,
        public string $confidence,
        public array $raw = [],
    ) {}

    public function isWrittenOff(): bool
    {
        return ! is_null($this->writeOffCategory) && $this->writeOffCategory !== 'none';
    }

    public function description(): string
    {
        return trim("{$this->year} {$this->make} {$this->model} {$this->derivative}");
    }
}
