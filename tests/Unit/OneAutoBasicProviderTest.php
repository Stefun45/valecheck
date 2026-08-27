<?php

namespace Tests\Unit;

use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\OneAuto\OneAutoClient;
use App\Services\RegistrationLookup\OneAutoBasicProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneAutoBasicProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function provider(): OneAutoBasicProvider
    {
        $client = new OneAutoClient('test-key', 'https://api.oneautoapi.com');

        return new OneAutoBasicProvider(new MotHistoryAndTaxStatusFetcher($client));
    }

    public function test_a_successful_preview_maps_identity_and_derives_mot_and_tax_status(): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/mothistoryandtaxstatus/v2*' => Http::response([
                'success' => true,
                'result' => [
                    'dvla_data' => ['tax_status' => 'Taxed', 'tax_expiry_date' => '2026-09-01'],
                    'dvsa_data' => [
                        'dvsa_vehicle_Data' => [
                            'vehicle_registration_mark' => 'AB12CDE',
                            'manufacturer_desc' => 'FORD',
                            'model_range_desc' => 'FIESTA',
                            'fuel_type_desc' => 'PETROL',
                            'colour' => 'BLUE',
                            'first_registration_date' => '2019-03-15',
                        ],
                        'mot_tests' => [
                            ['mot_test_date' => '2025-03-12', 'mot_test_result' => 'PASSED', 'mot_expiry_date' => now()->addMonths(6)->toDateString()],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $preview = $this->provider()->preview('AB12CDE');

        $this->assertNotNull($preview);
        $this->assertSame('AB12CDE', $preview->registration);
        $this->assertSame('FORD', $preview->make);
        $this->assertSame('FIESTA', $preview->model);
        $this->assertSame('BLUE', $preview->colour);
        $this->assertSame('PETROL', $preview->fuelType);
        $this->assertSame(2019, $preview->yearOfManufacture);
        $this->assertSame('Valid', $preview->motStatus);
        $this->assertSame('Taxed', $preview->taxStatus);
    }

    public function test_an_expired_mot_is_reported_as_expired_not_valid(): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/mothistoryandtaxstatus/v2*' => Http::response([
                'success' => true,
                'result' => [
                    'dvla_data' => ['tax_status' => 'Taxed'],
                    'dvsa_data' => [
                        'dvsa_vehicle_Data' => ['vehicle_registration_mark' => 'AB12CDE', 'manufacturer_desc' => 'FORD'],
                        'mot_tests' => [
                            ['mot_test_date' => '2024-01-01', 'mot_test_result' => 'PASSED', 'mot_expiry_date' => now()->subMonths(2)->toDateString()],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $preview = $this->provider()->preview('AB12CDE');

        $this->assertSame('Expired', $preview->motStatus);
    }

    public function test_a_failed_lookup_returns_null_rather_than_throwing(): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/mothistoryandtaxstatus/v2*' => Http::response(['success' => false, 'error' => 'Vehicle not found'], 404),
        ]);

        $this->assertNull($this->provider()->preview('ZZ99ZZZ'));
    }

    public function test_the_preview_and_a_subsequent_full_lookup_share_the_same_cached_call(): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/mothistoryandtaxstatus/v2*' => Http::response([
                'success' => true,
                'result' => [
                    'dvla_data' => ['tax_status' => 'Taxed'],
                    'dvsa_data' => [
                        'dvsa_vehicle_Data' => ['vehicle_registration_mark' => 'AB12CDE', 'manufacturer_desc' => 'FORD'],
                        'mot_tests' => [],
                    ],
                ],
            ], 200),
        ]);

        $this->provider()->preview('AB12CDE');
        $this->provider()->preview('AB 12 CDE'); // same plate, different formatting

        Http::assertSentCount(1);
    }
}
