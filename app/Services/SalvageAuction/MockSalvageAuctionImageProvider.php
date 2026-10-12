<?php

namespace App\Services\SalvageAuction;

/**
 * Deterministic simulated salvage auction photos for local development and
 * tests instead of calling One Auto API. Image URLs are placeholders —
 * never real — matching MockSalvageAuctionProvider's own placeholder style.
 */
class MockSalvageAuctionImageProvider implements SalvageAuctionImageProvider
{
    public function fetchImages(string $recordId, ?int $vehicleCheckId = null): array
    {
        return [
            'https://placehold.co/400x300?text=Salvage+Photo+1',
            'https://placehold.co/400x300?text=Salvage+Photo+2',
        ];
    }
}
