<?php

namespace App\Services\RegistrationLookup;

use App\DataTransferObjects\VehicleSpecPreview;
use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\OneAuto\OneAutoApiException;
use Carbon\Carbon;

/**
 * The free "is this your vehicle?" preview, shown before any payment.
 * Reuses the same MOT History & Tax Status call (and its cache) that the
 * paid report uses — see MotHistoryAndTaxStatusFetcher — rather than
 * calling a second, separate identity endpoint, so previewing costs the
 * same single call whether or not the customer goes on to pay.
 */
class OneAutoBasicProvider implements VehicleSpecPreviewProvider
{
    public function __construct(private readonly MotHistoryAndTaxStatusFetcher $motFetcher) {}

    public function preview(string $registration): ?VehicleSpecPreview
    {
        try {
            $mot = $this->motFetcher->fetch($registration);
        } catch (OneAutoApiException $e) {
            return null;
        }

        $vehicle = $mot['dvsa_data']['dvsa_vehicle_Data'] ?? [];
        $tests = $mot['dvsa_data']['mot_tests'] ?? [];

        if (empty($vehicle) && empty($tests)) {
            return null;
        }

        return new VehicleSpecPreview(
            registration: $vehicle['vehicle_registration_mark'] ?? MotHistoryAndTaxStatusFetcher::normalise($registration),
            make: $vehicle['manufacturer_desc'] ?? null,
            model: $vehicle['model_range_desc'] ?? null,
            colour: $vehicle['colour'] ?? null,
            fuelType: $vehicle['fuel_type_desc'] ?? null,
            yearOfManufacture: isset($vehicle['first_registration_date'])
                ? (int) Carbon::parse($vehicle['first_registration_date'])->format('Y')
                : null,
            engineCapacity: null, // Not returned by this endpoint.
            motStatus: $this->motStatus($tests),
            taxStatus: $mot['dvla_data']['tax_status'] ?? null,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $tests
     */
    private function motStatus(array $tests): ?string
    {
        if (empty($tests)) {
            return null;
        }

        $latestExpiry = $tests[0]['mot_expiry_date'] ?? null;

        if (! $latestExpiry) {
            return 'Expired';
        }

        return Carbon::parse($latestExpiry)->isFuture() ? 'Valid' : 'Expired';
    }
}
