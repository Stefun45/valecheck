<?php

namespace App\Services\RegistrationLookup;

use App\DataTransferObjects\VehicleSpecPreview;

/**
 * Deterministic simulated DVLA VES response, keyed off the registration.
 * Mirrors only the real fields DVLA actually returns — used until a real
 * DVLA_API_KEY is supplied.
 */
class MockDvlaProvider implements VehicleSpecPreviewProvider
{
    private const MAKES = ['BMW', 'FORD', 'VOLKSWAGEN', 'AUDI', 'VAUXHALL', 'TOYOTA', 'NISSAN'];

    private const COLOURS = ['BLACK', 'WHITE', 'SILVER', 'BLUE', 'RED', 'GREY'];

    private const FUEL_TYPES = ['PETROL', 'DIESEL', 'HYBRID ELECTRIC', 'ELECTRICITY'];

    public function preview(string $registration): ?VehicleSpecPreview
    {
        $registration = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $registration));

        if ($registration === '') {
            return null;
        }

        $seed = abs(crc32($registration));

        // Simulate a genuine "not found" for a slice of inputs, same as real DVLA would 404 an unknown plate.
        if ($seed % 13 === 0) {
            return null;
        }

        return new VehicleSpecPreview(
            registration: $registration,
            make: self::MAKES[$seed % count(self::MAKES)],
            model: null,
            colour: self::COLOURS[($seed >> 3) % count(self::COLOURS)],
            fuelType: self::FUEL_TYPES[($seed >> 5) % count(self::FUEL_TYPES)],
            yearOfManufacture: 2012 + ($seed % 13),
            engineCapacity: [1000, 1200, 1500, 1600, 2000, 3000][$seed % 6],
            motStatus: ($seed % 17 === 0) ? 'No details held by DVLA' : 'Valid',
            taxStatus: ($seed % 19 === 0) ? 'SORN' : 'Taxed',
        );
    }
}
