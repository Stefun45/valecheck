<?php

namespace App\DataTransferObjects;

/**
 * A cheap/free "quick look" shown before the paid full check runs. Sourced
 * from either the DVLA Vehicle Enquiry Service (free, but no model field —
 * DVLA genuinely doesn't return one) or One Auto's MOT History & Tax
 * Status call (paid per lookup, but does include model). $model is
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
        public ?string $taxExpiryDate = null,
        /**
         * Full MOT test history, same shape as VehicleData::$motHistory
         * (test_date, result, mileage, advisories) — only ever populated by
         * providers that genuinely return per-test detail (One Auto's MOT
         * History & Tax Status call). DVLA VES has no MOT test history at
         * all, so it's always empty there.
         *
         * @var array<int, array{test_date: ?string, result: ?string, mileage: ?int, advisories: array<int, string>}>
         */
        public array $motHistory = [],
    ) {}
}
