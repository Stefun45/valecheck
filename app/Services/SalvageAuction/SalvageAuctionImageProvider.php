<?php

namespace App\Services\SalvageAuction;

interface SalvageAuctionImageProvider
{
    /**
     * $vehicleCheckId is optional context for provider-side lookup logging
     * (see ProviderLookupLog) — it has no bearing on the fetch itself.
     *
     * @return array<int, string> image URLs for this salvage auction lot,
     *                            or [] if none are available.
     */
    public function fetchImages(string $recordId, ?int $vehicleCheckId = null): array;
}
