<?php

namespace App\Services\RegistrationLookup;

use App\DataTransferObjects\VehicleSpecPreview;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * VehicleMatic "Vehicle Details" product — a low-cost per-lookup check
 * (~£0.15/lookup on tier 1) used for the instant "is this your vehicle?"
 * confirmation. Distinct from the much more expensive "Full Vehicle Check"
 * product (see VehicleMaticProvider) which powers the actual paid reports.
 *
 * GET {base_url}/{vrm}
 * Header: X-VEHICLEMATIC-KEY
 * 404 = not found (not charged). 402/403/422/502 = error (see VehicleMatic's
 * published OpenAPI spec at vehiclematic.com/products/vehicle-details/openapi.json).
 */
class VehicleMaticBasicProvider implements VehicleSpecPreviewProvider
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $baseUrl,
    ) {}

    public function preview(string $registration): ?VehicleSpecPreview
    {
        if (empty($this->apiKey) || empty($this->baseUrl)) {
            throw new RuntimeException(
                'VehicleMatic is not configured. Set VEHICLEMATIC_API_KEY and VEHICLEMATIC_BASIC_BASE_URL, '
                .'or set REGISTRATION_LOOKUP_PROVIDER=mock for local development.'
            );
        }

        $vrm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $registration));

        $response = Http::withHeaders([
            'X-VEHICLEMATIC-KEY' => $this->apiKey,
        ])->get(rtrim($this->baseUrl, '/')."/{$vrm}");

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new RuntimeException("VehicleMatic Vehicle Details request failed with status {$response->status()}: {$response->body()}");
        }

        // VehicleMatic returns HTTP 200 even for auth/validation errors,
        // with {"error": true, "message": "..."} instead of a 4xx status.
        if ($response->json('error') === true) {
            throw new RuntimeException('VehicleMatic Vehicle Details returned an error: '.$response->json('message', 'unknown error'));
        }

        $data = $response->json('data', []);

        return new VehicleSpecPreview(
            registration: $data['registration_number'] ?? $vrm,
            make: $data['make'] ?? null,
            model: $data['model'] ?? null,
            colour: $data['colour'] ?? null,
            fuelType: $data['fuel_type'] ?? null,
            yearOfManufacture: $data['year_of_manufacture'] ?? null,
            engineCapacity: $data['engine_capacity'] ?? null,
            motStatus: $data['mot_status'] ?? null,
            taxStatus: $data['tax_status'] ?? null,
        );
    }
}
