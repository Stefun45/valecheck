<?php

namespace App\Services\VehicleData;

use App\DataTransferObjects\VehicleData;
use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\OneAuto\OneAutoApiException;
use App\Services\OneAuto\OneAutoClient;

/**
 * One Auto API — the paid "Full Vehicle Check" equivalent, powering the
 * actual ValeCheck/ValeCheck Plus reports. Two calls: Experian AutoCheck
 * (identity + full provenance) and One Auto's own MOT History & Tax
 * Status. See https://docs.oneautoapi.com/ — every field referenced here
 * comes from the real OpenAPI spec at https://swagger.oneautoapi.com/complete.json,
 * not invented.
 *
 * finance/stolen/write-off are the safety-critical provenance fields — if
 * AutoCheck's response is missing their *_qty counters, this throws rather
 * than defaulting them to "clean". A missing section silently read as
 * false is exactly what took VehicleMatic out of production.
 */
class OneAutoVehicleDataProvider implements VehicleDataProvider
{
    private const AUTOCHECK_ENDPOINT = 'experian/autocheck/v3';

    public function __construct(
        private readonly OneAutoClient $client,
        private readonly MotHistoryAndTaxStatusFetcher $motFetcher,
    ) {}

    public function getVehicle(string $registration, ?int $vehicleCheckId = null): VehicleData
    {
        $vrm = MotHistoryAndTaxStatusFetcher::normalise($registration);

        $autoCheck = $this->client->get(self::AUTOCHECK_ENDPOINT, $registration, [
            'vehicle_registration_mark' => $vrm,
        ], $vehicleCheckId);

        foreach (['finance_data_qty', 'stolen_vehicle_data_qty', 'condition_data_qty'] as $required) {
            if (! array_key_exists($required, $autoCheck)) {
                throw new OneAutoApiException(
                    self::AUTOCHECK_ENDPOINT,
                    null,
                    "AutoCheck response for {$vrm} is missing [{$required}] — treating as a failed lookup rather than assuming clean provenance.",
                );
            }
        }

        $mot = $this->motFetcher->fetch($registration, $vehicleCheckId);
        $motTests = $mot['dvsa_data']['mot_tests'] ?? [];

        [$writeOffCategory, $writeOffDate] = $this->writeOff($autoCheck);

        return new VehicleData(
            registration: $autoCheck['vehicle_registration_mark'] ?? $vrm,
            vin: $autoCheck['vehicle_identification_number'] ?? null,
            make: $autoCheck['dvla_manufacturer_desc'] ?? null,
            model: $autoCheck['dvla_model_desc'] ?? null,
            derivative: null, // AutoCheck doesn't return a separate derivative field.
            year: $autoCheck['manufactured_year'] ?? null,
            engine: isset($autoCheck['engine_capacity_cc']) ? "{$autoCheck['engine_capacity_cc']}cc" : null,
            fuel: $autoCheck['dvla_fuel_desc'] ?? null,
            transmission: $autoCheck['dvla_transmission_desc'] ?? null,
            colour: $autoCheck['colour'] ?? null,
            specification: null,
            writeOffCategory: $writeOffCategory,
            writeOffDate: $writeOffDate,
            financeMarker: ($autoCheck['finance_data_qty'] ?? 0) > 0,
            stolenMarker: $this->isStolen($autoCheck),
            scrappedMarker: $autoCheck['is_scrapped'] ?? null,
            imported: $autoCheck['is_imported'] ?? null,
            exported: $autoCheck['is_exported'] ?? null,
            previousKeepers: $autoCheck['keeper_data_items'][0]['number_previous_keepers'] ?? null,
            plateChanges: $autoCheck['cherished_data_qty'] ?? null,
            mileageAnomaly: null, // Not covered by AutoCheck — see ReportStatusSummary for the MOT-derived backwards-mileage check instead.
            motHistory: array_map(fn (array $test) => [
                'test_date' => $test['mot_test_date'] ?? null,
                'result' => $test['mot_test_result'] ?? null,
                'mileage' => $test['observation_mileage'] ?? null,
                'advisories' => array_map(
                    fn (array $c) => $c['comments'] ?? '',
                    $test['reason_for_refusal_and_comments'] ?? [],
                ),
            ], $motTests),
            keeperHistory: [],
            confidence: 'high',
            raw: ['autocheck' => $autoCheck, 'mot_and_tax' => $mot],
        );
    }

    /**
     * @param  array<string, mixed>  $autoCheck
     * @return array{0: ?string, 1: ?string}
     */
    private function writeOff(array $autoCheck): array
    {
        if (($autoCheck['condition_data_qty'] ?? 0) <= 0) {
            return [null, null];
        }

        $condition = $autoCheck['condition_data_items'][0] ?? [];

        return [
            $condition['recovered_category'] ?? null,
            $condition['date_of_loss'] ?? null,
        ];
    }

    private function isStolen(array $autoCheck): bool
    {
        if (($autoCheck['stolen_vehicle_data_qty'] ?? 0) <= 0) {
            return false;
        }

        return (bool) ($autoCheck['stolen_vehicle_data_items'][0]['is_stolen'] ?? true);
    }
}
