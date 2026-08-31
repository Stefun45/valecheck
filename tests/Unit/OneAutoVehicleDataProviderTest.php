<?php

namespace Tests\Unit;

use App\Models\ProviderLookupLog;
use App\Models\VehicleCheck;
use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\OneAuto\OneAutoApiException;
use App\Services\OneAuto\OneAutoClient;
use App\Services\VehicleData\OneAutoVehicleDataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneAutoVehicleDataProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function provider(): OneAutoVehicleDataProvider
    {
        $client = new OneAutoClient('test-key', 'https://api.oneautoapi.com');

        return new OneAutoVehicleDataProvider($client, new MotHistoryAndTaxStatusFetcher($client));
    }

    /**
     * @param  array<string, mixed>  $autoCheckResult
     */
    private function fakeAutoCheck(array $autoCheckResult, int $status = 200): void
    {
        Http::fake([
            'api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => true, 'result' => $autoCheckResult], $status),
        ]);
    }

    /**
     * @param  array<string, mixed>  $motResult
     */
    private function fakeMotAndTax(array $motResult, int $status = 200): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/mothistoryandtaxstatus/v2*' => Http::response(['success' => true, 'result' => $motResult], $status),
        ]);
    }

    private function fakeBoth(array $autoCheckResult, array $motResult): void
    {
        Http::fake([
            'api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => true, 'result' => $autoCheckResult], 200),
            'api.oneautoapi.com/oneauto/mothistoryandtaxstatus/v2*' => Http::response(['success' => true, 'result' => $motResult], 200),
        ]);
    }

    private function completeAutoCheck(array $overrides = []): array
    {
        return array_merge([
            'vehicle_registration_mark' => 'AB21ABC',
            'dvla_manufacturer_desc' => 'NISSAN',
            'dvla_model_desc' => 'QASHQAI ACENTA PREMIUM DCI',
            'dvla_fuel_desc' => 'DIESEL',
            'dvla_transmission_desc' => 'MANUAL 6 GEARS',
            'colour' => 'GREY',
            'manufactured_year' => 2017,
            'engine_capacity_cc' => 1997,
            'vehicle_identification_number' => 'WVWZZZ1JZXW000001',
            'is_scrapped' => false,
            'is_exported' => false,
            'is_imported' => false,
            'finance_data_qty' => 0,
            'stolen_vehicle_data_qty' => 0,
            'condition_data_qty' => 0,
            'keeper_data_items' => [['number_previous_keepers' => 2]],
            'cherished_data_qty' => 0,
        ], $overrides);
    }

    private function motAndTax(array $tests = []): array
    {
        return [
            'dvsa_data' => [
                'mot_tests' => $tests,
            ],
        ];
    }

    public function test_a_successful_complete_lookup_maps_every_field_correctly(): void
    {
        $this->fakeBoth(
            $this->completeAutoCheck(),
            $this->motAndTax([
                [
                    'mot_test_date' => '2025-03-12',
                    'mot_test_result' => 'PASSED',
                    'observation_mileage' => 48210,
                    'reason_for_refusal_and_comments' => [
                        ['type' => 'ADVISORY', 'comments' => 'Nearside front tyre worn close to the legal limit'],
                    ],
                ],
            ]),
        );

        $result = $this->provider()->getVehicle('AB21ABC');

        $this->assertSame('AB21ABC', $result->registration);
        $this->assertSame('NISSAN', $result->make);
        $this->assertSame('QASHQAI ACENTA PREMIUM DCI', $result->model);
        $this->assertSame('DIESEL', $result->fuel);
        $this->assertSame('MANUAL 6 GEARS', $result->transmission);
        $this->assertSame('GREY', $result->colour);
        $this->assertSame(2017, $result->year);
        $this->assertSame('1997cc', $result->engine);
        $this->assertSame('WVWZZZ1JZXW000001', $result->vin);
        $this->assertFalse($result->financeMarker);
        $this->assertFalse($result->stolenMarker);
        $this->assertFalse($result->scrappedMarker);
        $this->assertFalse($result->imported);
        $this->assertFalse($result->exported);
        $this->assertNull($result->writeOffCategory);
        $this->assertSame(2, $result->previousKeepers);
        $this->assertCount(1, $result->motHistory);
        $this->assertSame(48210, $result->motHistory[0]['mileage']);
        $this->assertSame(['Nearside front tyre worn close to the legal limit'], $result->motHistory[0]['advisories']);
        $this->assertCount(1, $result->keeperHistory);
        $this->assertSame(2, $result->keeperHistory[0]['keeper_number']);
    }

    public function test_keeper_history_maps_every_real_transfer_not_just_the_count(): void
    {
        // Confirmed against a real response: keeper_data_items is a
        // genuinely populated array with real dates — this was previously
        // hardcoded to an empty array and silently discarded.
        $this->fakeBoth(
            $this->completeAutoCheck([
                'keeper_data_items' => [
                    ['date_last_updated' => null, 'number_previous_keepers' => 4, 'date_of_last_keeper_change' => '2025-05-16'],
                    ['date_last_updated' => null, 'number_previous_keepers' => 3, 'date_of_last_keeper_change' => '2024-08-14'],
                    ['date_last_updated' => null, 'number_previous_keepers' => 2, 'date_of_last_keeper_change' => '2022-12-02'],
                    ['date_last_updated' => null, 'number_previous_keepers' => 1, 'date_of_last_keeper_change' => '2019-11-06'],
                ],
            ]),
            $this->motAndTax(),
        );

        $result = $this->provider()->getVehicle('DY17BXW');

        $this->assertSame(4, $result->previousKeepers);
        $this->assertCount(4, $result->keeperHistory);
        $this->assertSame(['keeper_number' => 4, 'date_of_transfer' => '2025-05-16'], $result->keeperHistory[0]);
        $this->assertSame(['keeper_number' => 1, 'date_of_transfer' => '2019-11-06'], $result->keeperHistory[3]);
    }

    public function test_write_off_category_is_derived_from_condition_data_when_present(): void
    {
        $this->fakeBoth(
            $this->completeAutoCheck([
                'condition_data_qty' => 1,
                'condition_data_items' => [
                    ['recovered_category' => 'N', 'date_of_loss' => '2020-03-15'],
                ],
            ]),
            $this->motAndTax(),
        );

        $result = $this->provider()->getVehicle('AB21ABC');

        $this->assertSame('N', $result->writeOffCategory);
        $this->assertSame('2020-03-15', $result->writeOffDate);
        $this->assertTrue($result->isWrittenOff());
    }

    public function test_write_off_category_is_parsed_from_vehicle_status_on_a_real_cat_n_response(): void
    {
        // Confirmed against a real sandbox Cat N response: recovered_category
        // and recovered_category_desc are both null even though a write-off
        // is genuinely present — this was silently missed entirely until a
        // live test surfaced it, because the earlier test fixture above
        // invented a 'recovered_category' field that the real API never
        // actually populates.
        $this->fakeBoth(
            $this->completeAutoCheck([
                'condition_data_qty' => 1,
                'condition_data_items' => [
                    [
                        'date_of_loss' => '2025-04-06',
                        'vehicle_status' => 'CAT N NON STRUCTURAL DAMAGE',
                        'theft_indictor_literal' => 'NOT STOLEN',
                        'recovered_category' => null,
                        'recovered_category_desc' => null,
                        'theft_indicator' => 'N',
                        'cause_of_damage' => 'Accident',
                        'damage_location_items' => [
                            ['damage_location_desc' => 'Front'],
                            ['damage_location_desc' => 'FrontNearside'],
                        ],
                    ],
                ],
            ]),
            $this->motAndTax(),
        );

        $result = $this->provider()->getVehicle('DY17BXW');

        $this->assertSame('N', $result->writeOffCategory);
        $this->assertSame('2025-04-06', $result->writeOffDate);
        $this->assertTrue($result->isWrittenOff());
    }

    public function test_write_off_category_s_is_parsed_from_a_structural_damage_vehicle_status(): void
    {
        $this->fakeBoth(
            $this->completeAutoCheck([
                'condition_data_qty' => 1,
                'condition_data_items' => [
                    ['date_of_loss' => '2024-01-01', 'vehicle_status' => 'CAT S STRUCTURAL DAMAGE', 'recovered_category' => null],
                ],
            ]),
            $this->motAndTax(),
        );

        $result = $this->provider()->getVehicle('AB21ABC');

        $this->assertSame('S', $result->writeOffCategory);
    }

    public function test_finance_and_stolen_are_derived_from_their_qty_counters(): void
    {
        $this->fakeBoth(
            $this->completeAutoCheck([
                'finance_data_qty' => 1,
                'stolen_vehicle_data_qty' => 1,
                'stolen_vehicle_data_items' => [['is_stolen' => true]],
            ]),
            $this->motAndTax(),
        );

        $result = $this->provider()->getVehicle('AB21ABC');

        $this->assertTrue($result->financeMarker);
        $this->assertTrue($result->stolenMarker);
    }

    public function test_a_missing_provenance_qty_field_fails_the_whole_lookup_rather_than_assuming_clean(): void
    {
        // The exact bug that took VehicleMatic out of production: a
        // response missing its provenance section must never be read as
        // "checked and clean".
        $autoCheck = $this->completeAutoCheck();
        unset($autoCheck['finance_data_qty']);

        $this->fakeBoth($autoCheck, $this->motAndTax());

        $this->expectException(OneAutoApiException::class);

        $this->provider()->getVehicle('AB21ABC');
    }

    public function test_scrapped_exported_imported_are_null_not_false_when_absent(): void
    {
        $autoCheck = $this->completeAutoCheck();
        unset($autoCheck['is_scrapped'], $autoCheck['is_exported'], $autoCheck['is_imported']);

        $this->fakeBoth($autoCheck, $this->motAndTax());

        $result = $this->provider()->getVehicle('AB21ABC');

        $this->assertNull($result->scrappedMarker);
        $this->assertNull($result->exported);
        $this->assertNull($result->imported);
    }

    public function test_vehicle_not_found_throws(): void
    {
        Http::fake([
            'api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => false, 'error' => 'Vehicle not found'], 404),
        ]);

        $this->expectException(OneAutoApiException::class);

        $this->provider()->getVehicle('ZZ99ZZZ');
    }

    public function test_a_provider_timeout_throws_and_is_logged(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        try {
            $this->provider()->getVehicle('AB21ABC');
            $this->fail('Expected OneAutoApiException to be thrown.');
        } catch (OneAutoApiException $e) {
            // expected
        }

        $this->assertSame(1, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_FAILED)->count());
    }

    public function test_a_success_false_response_throws(): void
    {
        Http::fake([
            'api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => false, 'error' => 'Invalid API key'], 200),
        ]);

        $this->expectException(OneAutoApiException::class);

        $this->provider()->getVehicle('AB21ABC');
    }

    public function test_every_successful_call_is_logged_with_the_vehicle_check_id(): void
    {
        $check = VehicleCheck::factory()->create();

        $this->fakeBoth($this->completeAutoCheck(), $this->motAndTax());

        $this->provider()->getVehicle('AB21ABC', $check->id);

        $this->assertSame(2, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_SUCCESS)->count());
        $this->assertSame(2, ProviderLookupLog::where('vehicle_check_id', $check->id)->count());
    }

    public function test_the_api_key_never_appears_in_any_logged_row(): void
    {
        $check = VehicleCheck::factory()->create();
        $client = new OneAutoClient('super-secret-key-do-not-leak', 'https://api.oneautoapi.com');
        $provider = new OneAutoVehicleDataProvider($client, new MotHistoryAndTaxStatusFetcher($client));

        $this->fakeBoth($this->completeAutoCheck(), $this->motAndTax());

        $provider->getVehicle('AB21ABC', $check->id);

        foreach (ProviderLookupLog::all() as $log) {
            $this->assertStringNotContainsString('super-secret-key-do-not-leak', (string) $log->error_message);
            $this->assertStringNotContainsString('super-secret-key-do-not-leak', json_encode($log->toArray()));
        }
    }

    public function test_the_mot_and_tax_call_is_reused_from_cache_within_the_same_registration(): void
    {
        $this->fakeBoth($this->completeAutoCheck(), $this->motAndTax());

        $this->provider()->getVehicle('AB21ABC');
        $this->provider()->getVehicle('AB21ABC');

        // Two full lookups, but the MOT/Tax call should only have hit the
        // API once — the second reused the cache.
        $this->assertSame(1, ProviderLookupLog::where('endpoint', 'oneauto/mothistoryandtaxstatus/v2')->count());
        $this->assertSame(2, ProviderLookupLog::where('endpoint', 'experian/autocheck/v3')->count());
    }
}
