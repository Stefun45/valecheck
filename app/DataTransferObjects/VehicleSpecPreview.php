<?php

namespace App\DataTransferObjects;

/**
 * A cheap/free "quick look" shown before the paid full check runs. Sourced
 * from either the DVLA Vehicle Enquiry Service (free, but no model field —
 * DVLA genuinely doesn't return one) or VehicleMatic's low-cost "Vehicle
 * Details" product (paid per lookup, but does include model). $model is
 * therefore null when the DVLA provider is in use.
 */
final readonly class VehicleSpecPreview
{
    public function __construct(
        public string $registration,
        public ?string $make,
        public ?string $model,
        public ?string $colour,
        public ?string $fuelType,
        public ?int $yearOfManufacture,
        public ?int $engineCapacity,
        public ?string $motStatus,
        public ?string $taxStatus,
    ) {}
}
