<?php

namespace App\Services\VehicleTax;

use App\DataTransferObjects\VehicleTaxCostData;

/**
 * Deterministic simulated tax cost for local development and tests instead
 * of calling One Auto API. Unlike salvage history, most real vehicles do
 * have a tax rate, so this is available far more often than not.
 */
class MockVehicleTaxCostProvider implements VehicleTaxCostProvider
{
    private const BANDS = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

    public function check(string $registration, ?int $vehicleCheckId = null): VehicleTaxCostData
    {
        $seed = 0;
        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $registration))) as $char) {
            $seed += ord($char);
        }

        if ($seed % 11 === 0) {
            return new VehicleTaxCostData(available: false);
        }

        $annualRate = 20 + (($seed % 40) * 10);

        return new VehicleTaxCostData(
            available: true,
            annualRate: (float) $annualRate,
            sixMonthRate: round($annualRate * 0.55, 2),
            taxClass: self::BANDS[$seed % count(self::BANDS)],
        );
    }
}
