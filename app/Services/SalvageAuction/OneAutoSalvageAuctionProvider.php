<?php

namespace App\Services\SalvageAuction;

use App\DataTransferObjects\SalvageAuctionData;
use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\OneAuto\OneAutoApiException;
use App\Services\OneAuto\OneAutoClient;

/**
 * One Auto API — SalvageGuide "Salvage Check from VRM"
 * (carguide/salvagecheck/v2). Has this vehicle previously been listed at a
 * salvage/insurance auction, including any photos taken at the time.
 *
 * A failed/unavailable check degrades to "no record found" rather than
 * failing the whole Plus report — this is additive provenance, not a
 * safety-critical field like finance/stolen/write-off.
 */
class OneAutoSalvageAuctionProvider implements SalvageAuctionProvider
{
    private const ENDPOINT = 'carguide/salvagecheck/v2';

    public function __construct(private readonly OneAutoClient $client) {}

    public function check(string $registration, ?int $vehicleCheckId = null): SalvageAuctionData
    {
        try {
            $result = $this->client->get(self::ENDPOINT, $registration, [
                'vehicle_registration_mark' => MotHistoryAndTaxStatusFetcher::normalise($registration),
            ], $vehicleCheckId);
        } catch (OneAutoApiException) {
            return new SalvageAuctionData(recordFound: false, records: []);
        }

        if (empty($result['salvage_auction_record_found'])) {
            return new SalvageAuctionData(recordFound: false, records: []);
        }

        $records = array_map(fn (array $record) => [
            'lotDescription' => $record['salvage_auction_lot_desc'] ?? null,
            'lotDate' => $record['salvage_auction_lot_date'] ?? null,
            'mileage' => $record['mileage'] ?? null,
            'primaryDamageDescription' => $record['primary_damage_desc'] ?? null,
            'secondaryDamageDescription' => $record['secondary_damage_desc'] ?? null,
            'location' => $record['salvage_auction_location'] ?? null,
            'imageUrls' => $record['external_image_urls'] ?? [],
        ], $result['salvage_auction_records'] ?? []);

        return new SalvageAuctionData(recordFound: true, records: $records);
    }
}
