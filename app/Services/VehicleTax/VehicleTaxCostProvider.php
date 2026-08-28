<?php

namespace App\Services\VehicleTax;

use App\DataTransferObjects\VehicleTaxCostData;

interface VehicleTaxCostProvider
{
    /**
     * $vehicleCheckId is optional context for provider-side lookup logging
     * (see ProviderLookupLog) — it has no bearing on the check itself.
     */
    public function check(string $registration, ?int $vehicleCheckId = null): VehicleTaxCostData;
}
