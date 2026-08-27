<?php

namespace App\Services\VehicleData;

use App\DataTransferObjects\VehicleData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * VehicleMatic "Full Vehicle Check" product — the expensive per-lookup
 * check (tiered ~£2.45-£3.25/lookup) that powers the actual paid ValeCheck
 * Check/Plus/Rebuild reports. Distinct from the cheap "Vehicle Details"
 * product (see RegistrationLookup\VehicleMaticBasicProvider) used for the
 * free-standing "is this your vehicle?" confirmation.
 *
 * GET {base_url}/{vrm}
 * Header: X-VEHICLEMATIC-KEY
 * 404 = not found. 402/403/422/502 = error (see VehicleMatic's published
 * OpenAPI spec at vehiclematic.com/products/full-vehicle-check/openapi.json).
 */
class VehicleMaticProvider implements VehicleDataProvider
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $baseUrl,
    ) {}

    public function getVehicle(string $registration): VehicleData
    {
        if (empty($this->apiKey) || empty($this->baseUrl)) {
            throw new RuntimeException(
                'VehicleMatic is not configured. Set VEHICLEMATIC_API_KEY and VEHICLEMATIC_BASE_URL, '
                .'or set VEHICLE_DATA_PROVIDER=mock for local development.'
            );
        }

        $vrm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $registration));

        $response = Http::withHeaders([
            'X-VEHICLEMATIC-KEY' => $this->apiKey,
        ])->get(rtrim($this->baseUrl, '/')."/{$vrm}");

        if ($response->status() === 404) {
            throw new RuntimeException("VehicleMatic Full Vehicle Check found no record for registration {$vrm}.");
        }

        if ($response->failed()) {
            throw new RuntimeException("VehicleMatic Full Vehicle Check request failed with status {$response->status()}: {$response->body()}");
        }

        // VehicleMatic returns HTTP 200 even for auth/validation errors,
        // with {"error": true, "message": "..."} instead of a 4xx status.
        if ($response->json('error') === true) {
            throw new RuntimeException('VehicleMatic Full Vehicle Check returned an error: '.$response->json('message', 'unknown error'));
        }

        $data = $response->json('data', []);

        $provenance = $data['provenance'] ?? [];
        $keeper = $provenance['keeper'] ?? [];
        $mileage = $provenance['mileage'] ?? [];
        $plateChanges = $provenance['plate_changes'] ?? [];

        return new VehicleData(
            registration: $data['registration_number'] ?? $vrm,
            vin: null, // VehicleMatic only exposes vin_last_5, not the full VIN.
            make: $data['make'] ?? null,
            model: $data['model'] ?? null,
            derivative: null,
            year: $data['year_of_manufacture'] ?? null,
            engine: isset($data['engine_capacity']) ? "{$data['engine_capacity']}cc" : null,
            fuel: $data['fuel_type'] ?? null,
            transmission: null,
            colour: $data['colour'] ?? null,
            specification: null,
            writeOffCategory: ($provenance['write_off'] ?? false) ? ($provenance['write_off_category'] ?? null) : null,
            writeOffDate: $provenance['write_off_date'] ?? null,
            financeMarker: (bool) ($provenance['outstanding_finance'] ?? false),
            stolenMarker: (bool) ($provenance['stolen'] ?? false),
            scrappedMarker: (bool) ($provenance['scrapped'] ?? false),
            imported: (bool) ($provenance['imported'] ?? false),
            exported: (bool) ($provenance['exported'] ?? false),
            previousKeepers: $keeper['previous_keeper_count'] ?? null,
            plateChanges: $plateChanges['count'] ?? null,
            mileageAnomaly: (bool) ($mileage['anomaly_detected'] ?? false),
            motHistory: array_map(fn (array $test) => [
                'test_date' => $test['test_date'] ?? null,
                'result' => $test['result'] ?? null,
                'mileage' => $test['odometer'] ?? null,
                'advisories' => $test['advisories'] ?? [],
            ], $data['mot_history'] ?? []),
            keeperHistory: [],
            confidence: 'high',
            raw: $data,
        );
    }
}
