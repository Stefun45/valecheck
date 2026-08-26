<?php

namespace App\Services\RegistrationLookup;

use App\DataTransferObjects\VehicleSpecPreview;

interface VehicleSpecPreviewProvider
{
    /**
     * Returns null when the registration is not found (e.g. a genuine
     * "not found" from DVLA) — never a fabricated result.
     */
    public function preview(string $registration): ?VehicleSpecPreview;
}
