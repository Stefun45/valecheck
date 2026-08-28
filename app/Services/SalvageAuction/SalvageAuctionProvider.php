<?php

namespace App\Services\SalvageAuction;

use App\DataTransferObjects\SalvageAuctionData;

interface SalvageAuctionProvider
{
    /**
     * $vehicleCheckId is optional context for provider-side lookup logging
     * (see ProviderLookupLog) — it has no bearing on the check itself.
     */
    public function check(string $registration, ?int $vehicleCheckId = null): SalvageAuctionData;
}
