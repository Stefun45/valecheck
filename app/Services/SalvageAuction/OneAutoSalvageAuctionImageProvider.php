<?php

namespace App\Services\SalvageAuction;

use App\Models\ProviderEndpointCost;
use App\Services\OneAuto\OneAutoApiException;
use App\Services\OneAuto\OneAutoClient;

/**
 * One Auto API — SalvageGuide "Third Party Images"
 * (salvageguide/thirdpartyimages). A separate, much higher-coverage (~85%,
 * vs under 5% for carguide/salvagecheck/v2's own external_image_urls)
 * image endpoint for a salvage auction lot already identified by
 * OneAutoSalvageAuctionProvider, keyed on that record's Lot ID.
 *
 * Billing here is conditional on HTTP status, unlike every other One Auto
 * endpoint in this codebase: per One Auto's own documentation, only a
 * genuine HTTP 200 is chargeable — 202 (processing), 204 (no content),
 * 206 (partial content) and any 4xx/5xx response are all explicitly
 * non-chargeable. OneAutoClient's default behaviour (a flat lookup from
 * ProviderEndpointCost regardless of outcome) is correct for every other
 * endpoint but wrong here, so this is the one caller in the codebase that
 * passes a cost-resolver override into OneAutoClient::get().
 *
 * The returned link expires after ~12 hours — the caller (see
 * RetrieveSalvageAuctionImages) downloads and stores the bytes immediately
 * rather than keeping this URL.
 *
 * A failed/unavailable fetch degrades to no images, the same "additive
 * provenance, not safety-critical" philosophy as OneAutoSalvageAuctionProvider.
 */
class OneAutoSalvageAuctionImageProvider implements SalvageAuctionImageProvider
{
    private const ENDPOINT = 'salvageguide/thirdpartyimages';

    public function __construct(private readonly OneAutoClient $client) {}

    public function fetchImages(string $recordId, ?int $vehicleCheckId = null): array
    {
        try {
            $result = $this->client->get(
                self::ENDPOINT,
                // No vehicle registration applies to this call — the lot ID
                // is the only identifier it's keyed on, so that's what gets
                // logged against ProviderLookupLog's "registration" column
                // instead.
                $recordId,
                ['salvage_auction_reference' => $recordId],
                $vehicleCheckId,
                fn (?int $httpStatus) => $this->costFor($httpStatus),
            );
        } catch (OneAutoApiException) {
            return [];
        }

        return $result['third_party_image_urls'] ?? [];
    }

    /**
     * Only a genuine HTTP 200 is chargeable for this endpoint — everything
     * else (202/204/206/4xx/5xx) is explicitly free per One Auto's docs.
     */
    private function costFor(?int $httpStatus): float
    {
        if ($httpStatus !== 200) {
            return 0.0;
        }

        return (float) (ProviderEndpointCost::where('endpoint', self::ENDPOINT)->value('cost_net') ?? 0);
    }
}
