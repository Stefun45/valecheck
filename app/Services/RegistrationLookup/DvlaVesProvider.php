<?php

namespace App\Services\RegistrationLookup;

use App\DataTransferObjects\VehicleSpecPreview;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real DVLA Vehicle Enquiry Service (VES) integration.
 *
 * POST {base_url}/vehicle-enquiry/v1/vehicles
 * Headers: x-api-key, Content-Type: application/json
 * Body: {"registrationNumber": "AB12CDE"} (no spaces/non-alphanumeric)
 * 404 = not found. 400/500/503 = error. 429 = rate limited.
 *
 * Confirmed against the DVLA developer portal's Vehicle Enquiry Service
 * description. Response does not include model/derivative — only the
 * fields mapped below.
 */
class DvlaVesProvider implements VehicleSpecPreviewProvider
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $baseUrl,
    ) {}

    public function preview(string $registration): ?VehicleSpecPreview
    {
        if (empty($this->apiKey) || empty($this->baseUrl)) {
            throw new RuntimeException(
                'DVLA is not configured. Set DVLA_API_KEY and DVLA_BASE_URL, or set DVLA_PROVIDER=mock for local development.'
            );
        }

        $registration = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $registration));

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post(rtrim($this->baseUrl, '/').'/vehicle-enquiry/v1/vehicles', [
            'registrationNumber' => $registration,
        ]);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            throw new RuntimeException("DVLA VES request failed with status {$response->status()}: {$response->body()}");
        }

        $data = $response->json();

        return new VehicleSpecPreview(
            registration: $data['registrationNumber'] ?? $registration,
            make: $data['make'] ?? null,
            model: null,
            colour: $data['colour'] ?? null,
            fuelType: $data['fuelType'] ?? null,
            yearOfManufacture: $data['yearOfManufacture'] ?? null,
            engineCapacity: $data['engineCapacity'] ?? null,
            motStatus: $data['motStatus'] ?? null,
            taxStatus: $data['taxStatus'] ?? null,
        );
    }
}
