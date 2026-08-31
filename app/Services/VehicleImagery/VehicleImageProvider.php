<?php

namespace App\Services\VehicleImagery;

use App\DataTransferObjects\VehicleImageData;

interface VehicleImageProvider
{
    /**
     * $colour is the vehicle's real detected colour, used to request a
     * matching image variant where the provider has one available —
     * $vehicleCheckId is optional context for provider-side lookup
     * logging (see ProviderLookupLog) — it has no bearing on the check
     * itself.
     */
    public function fetch(string $registration, ?string $colour, ?int $vehicleCheckId = null): VehicleImageData;
}
