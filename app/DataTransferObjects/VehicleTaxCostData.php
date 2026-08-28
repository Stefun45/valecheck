<?php

namespace App\DataTransferObjects;

/**
 * Internal representation of a "what does it cost to tax" lookup — a
 * ValeCheck Plus-only extra (One Auto's Vehicle Tax from VRM, 10p/call).
 * Not to be confused with the free tax_status/tax_expiry_date already
 * shown from the MOT History & Tax Status call — this is the actual rate.
 */
final readonly class VehicleTaxCostData
{
    public function __construct(
        public bool $available,
        public ?float $annualRate = null,
        public ?float $sixMonthRate = null,
        public ?string $taxClass = null,
    ) {}
}
