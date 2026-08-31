<?php

namespace App\Services\VehicleData;

use App\DataTransferObjects\VehicleData;
use Carbon\Carbon;

/**
 * Deterministic simulated vehicle data, keyed off the registration so the
 * same plate always returns the same result. Used for local development
 * and tests instead of calling One Auto API.
 */
class MockVehicleDataProvider implements VehicleDataProvider
{
    private const VEHICLES = [
        ['make' => 'BMW', 'model' => 'M4', 'derivative' => 'Competition', 'fuel' => 'Petrol', 'transmission' => 'Automatic', 'engine' => '3.0 Twin-Turbo'],
        ['make' => 'Ford', 'model' => 'Focus', 'derivative' => 'ST-Line', 'fuel' => 'Petrol', 'transmission' => 'Manual', 'engine' => '1.5 EcoBoost'],
        ['make' => 'Volkswagen', 'model' => 'Golf', 'derivative' => 'GTD', 'fuel' => 'Diesel', 'transmission' => 'Manual', 'engine' => '2.0 TDI'],
        ['make' => 'Audi', 'model' => 'A4', 'derivative' => 'S Line', 'fuel' => 'Diesel', 'transmission' => 'Automatic', 'engine' => '2.0 TDI'],
        ['make' => 'Vauxhall', 'model' => 'Corsa', 'derivative' => 'SRi', 'fuel' => 'Petrol', 'transmission' => 'Manual', 'engine' => '1.2 Turbo'],
    ];

    private const COLOURS = ['Black', 'White', 'Silver', 'Blue', 'Red', 'Grey'];

    private const WRITE_OFF_CATEGORIES = [null, null, null, null, 'N', 'S'];

    private const ADVISORIES = [
        'Nearside front tyre worn close to the legal limit',
        'Offside rear brake disc worn',
        'Front wiper blade deteriorated',
        'Exhaust has a minor leak',
        'Nearside headlamp aim slightly high',
        'Rear number plate lamp not working',
    ];

    public function getVehicle(string $registration, ?int $vehicleCheckId = null): VehicleData
    {
        $registration = strtoupper(preg_replace('/\s+/', '', $registration));
        $seed = $this->seedFor($registration);

        $vehicle = self::VEHICLES[$seed % count(self::VEHICLES)];
        $colour = self::COLOURS[($seed >> 3) % count(self::COLOURS)];
        $writeOffCategory = self::WRITE_OFF_CATEGORIES[($seed >> 5) % count(self::WRITE_OFF_CATEGORIES)];
        $year = 2015 + ($seed % 10);
        $financeMarker = ($seed % 4) === 0;
        $stolenMarker = false;
        $highRiskMarker = ($seed % 13) === 0;
        $scrappedMarker = false;
        $imported = ($seed % 11) === 0;
        $mileageAnomaly = ($seed % 7) === 0;
        $previousKeepers = 1 + ($seed % 4);
        $plateChanges = ($seed % 9) === 0 ? 1 : 0;

        $motHistory = $this->buildMotHistory($seed, $year);
        $keeperHistory = $this->buildKeeperHistory($seed, $previousKeepers);
        $plateChangeHistory = $this->buildPlateChangeHistory($seed, $registration, $plateChanges);

        return new VehicleData(
            registration: $registration,
            vin: 'SIM'.str_pad((string) $seed, 14, '0', STR_PAD_LEFT),
            make: $vehicle['make'],
            model: $vehicle['model'],
            derivative: $vehicle['derivative'],
            year: $year,
            engine: $vehicle['engine'],
            fuel: $vehicle['fuel'],
            transmission: $vehicle['transmission'],
            colour: $colour,
            specification: null,
            writeOffCategory: $writeOffCategory,
            writeOffDate: $writeOffCategory ? Carbon::createFromDate($year + 1, 3, 15)->toDateString() : null,
            financeMarker: $financeMarker,
            stolenMarker: $stolenMarker,
            highRiskMarker: $highRiskMarker,
            scrappedMarker: $scrappedMarker,
            imported: $imported,
            exported: false,
            previousKeepers: $previousKeepers,
            plateChanges: $plateChanges,
            mileageAnomaly: $mileageAnomaly,
            motHistory: $motHistory,
            keeperHistory: $keeperHistory,
            confidence: 'high',
            raw: ['simulated' => true, 'seed' => $seed],
            colourChanges: ($seed % 15) === 0 ? 1 : 0,
            wasExported: ($seed % 17) === 0,
            vehicleIdentityChecks: 0,
            v5cReissues: 1 + ($seed % 4),
            previousSearches: $seed % 20,
            vrmMatches: true,
            vinMatches: true,
            plateChangeHistory: $plateChangeHistory,
        );
    }

    private function seedFor(string $registration): int
    {
        return abs(crc32($registration));
    }

    private function buildMotHistory(int $seed, int $year): array
    {
        $history = [];
        $currentYear = (int) date('Y');
        $mileage = 8000 + ($seed % 5000);

        for ($testYear = $year + 3; $testYear <= $currentYear; $testYear++) {
            $mileage += 6000 + ($seed % 4000);
            $testSeed = $seed + $testYear;

            $history[] = [
                'test_date' => Carbon::createFromDate($testYear, 6, 1)->toDateString(),
                'result' => ($testSeed % 6) === 0 ? 'fail' : 'pass',
                'mileage' => $mileage,
                'advisories' => ($testSeed % 3) === 0 ? [] : [self::ADVISORIES[$testSeed % count(self::ADVISORIES)]],
            ];
        }

        return $history;
    }

    private function buildKeeperHistory(int $seed, int $previousKeepers): array
    {
        $history = [];

        for ($i = 1; $i <= $previousKeepers; $i++) {
            $history[] = [
                'keeper_number' => $i,
                'date_of_transfer' => Carbon::now()->subYears($previousKeepers - $i + 1)->toDateString(),
            ];
        }

        return $history;
    }

    private function buildPlateChangeHistory(int $seed, string $currentRegistration, int $plateChanges): array
    {
        if ($plateChanges === 0) {
            return [];
        }

        $simulatedPreviousPlate = 'SIM'.str_pad((string) ($seed % 100), 2, '0', STR_PAD_LEFT).'ABC';

        return [[
            'date' => Carbon::now()->subYears(2)->toDateString(),
            'from' => $simulatedPreviousPlate,
            'to' => $currentRegistration,
            'type' => 'Data Move',
        ]];
    }
}
