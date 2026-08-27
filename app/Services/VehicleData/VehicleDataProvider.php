<?php

namespace App\Services\VehicleData;

use App\DataTransferObjects\VehicleData;

interface VehicleDataProvider
{
    /**
     * $vehicleCheckId is optional context for provider-side lookup logging
     * (see ProviderLookupLog) — it has no bearing on the lookup itself.
     */
    public function getVehicle(string $registration, ?int $vehicleCheckId = null): VehicleData;
}
