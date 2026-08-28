<?php

namespace App\Services\SalvageAuction;

use App\DataTransferObjects\SalvageAuctionData;
use Carbon\Carbon;

/**
 * Deterministic simulated salvage auction data for local development and
 * tests instead of calling One Auto API. Image URLs are placeholders —
 * never real — since this is mock data only.
 */
class MockSalvageAuctionProvider implements SalvageAuctionProvider
{
    public function check(string $registration, ?int $vehicleCheckId = null): SalvageAuctionData
    {
        $seed = 0;
        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $registration))) as $char) {
            $seed += ord($char);
        }

        // Mostly "not found" — a salvage auction record is the exception,
        // not the norm, even in mock data.
        if ($seed % 5 !== 0) {
            return new SalvageAuctionData(recordFound: false, records: []);
        }

        return new SalvageAuctionData(
            recordFound: true,
            records: [
                [
                    'lotDescription' => 'Category N — front end collision damage',
                    'lotDate' => Carbon::now()->subYears(2)->toDateString(),
                    'mileage' => 32000 + ($seed * 37),
                    'primaryDamageDescription' => 'Front bumper and headlight',
                    'secondaryDamageDescription' => 'Nearside front wing',
                    'location' => 'Copart Bedford',
                    'imageUrls' => [
                        'https://placehold.co/400x300?text=Salvage+Photo+1',
                        'https://placehold.co/400x300?text=Salvage+Photo+2',
                    ],
                ],
            ],
        );
    }
}
