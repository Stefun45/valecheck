<?php

namespace Tests\Unit;

use App\Services\VehicleData\VehicleMaticProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class VehicleMaticProviderTest extends TestCase
{
    public function test_successful_lookup_maps_history_and_provenance_fields(): void
    {
        Http::fake([
            'vehiclematic.com/*' => Http::response([
                'data' => [
                    'registration_number' => 'S1CATN',
                    'make' => 'AUDI',
                    'model' => 'A4',
                    'colour' => 'GREY',
                    'fuel_type' => 'DIESEL',
                    'engine_capacity' => 2000,
                    'year_of_manufacture' => 2017,
                    'mot_history' => [
                        ['test_date' => '2023-06-01', 'result' => 'pass', 'odometer' => 42000],
                    ],
                    'provenance' => [
                        'write_off' => true,
                        'write_off_category' => 'S',
                        'write_off_date' => '2020-03-15',
                        'outstanding_finance' => true,
                        'stolen' => false,
                        'scrapped' => false,
                        'imported' => false,
                        'exported' => false,
                        'keeper' => ['previous_keeper_count' => 3],
                        'mileage' => ['anomaly_detected' => true],
                        'plate_changes' => ['count' => 1],
                    ],
                ],
                'credit_balance' => 100,
            ], 200),
        ]);

        $provider = new VehicleMaticProvider('test-key', 'https://vehiclematic.com/products/full-vehicle-check/api/live');
        $result = $provider->getVehicle('S1 CATN');

        $this->assertSame('S1CATN', $result->registration);
        $this->assertSame('AUDI', $result->make);
        $this->assertSame('A4', $result->model);
        $this->assertSame('S', $result->writeOffCategory);
        $this->assertTrue($result->financeMarker);
        $this->assertTrue($result->mileageAnomaly);
        $this->assertSame(3, $result->previousKeepers);
        $this->assertSame(1, $result->plateChanges);
        $this->assertCount(1, $result->motHistory);
        $this->assertSame(42000, $result->motHistory[0]['mileage']);
    }

    public function test_no_write_off_flag_means_no_category_is_reported(): void
    {
        Http::fake([
            'vehiclematic.com/*' => Http::response([
                'data' => [
                    'registration_number' => 'AB12CDE',
                    'make' => 'FORD',
                    'provenance' => ['write_off' => false, 'write_off_category' => null],
                ],
            ], 200),
        ]);

        $provider = new VehicleMaticProvider('test-key', 'https://vehiclematic.com/products/full-vehicle-check/api/live');
        $result = $provider->getVehicle('AB12CDE');

        $this->assertNull($result->writeOffCategory);
        $this->assertFalse($result->isWrittenOff());
    }

    public function test_not_found_throws(): void
    {
        Http::fake(['vehiclematic.com/*' => Http::response([], 404)]);

        $provider = new VehicleMaticProvider('test-key', 'https://vehiclematic.com/products/full-vehicle-check/api/live');

        $this->expectException(RuntimeException::class);
        $provider->getVehicle('ZZ99ZZZ');
    }

    public function test_an_error_body_returned_with_http_200_throws_instead_of_silently_returning_nulls(): void
    {
        // VehicleMatic returns an invalid-key error with a 200 status rather
        // than a 4xx, so $response->failed() alone won't catch it.
        Http::fake([
            'vehiclematic.com/*' => Http::response([
                'error' => true,
                'message' => 'The X-VEHICLEMATIC-KEY provided is not valid.',
            ], 200),
        ]);

        $provider = new VehicleMaticProvider('bad-key', 'https://vehiclematic.com/products/full-vehicle-check/api/live');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The X-VEHICLEMATIC-KEY provided is not valid.');
        $provider->getVehicle('AB12CDE');
    }

    public function test_missing_configuration_throws(): void
    {
        $provider = new VehicleMaticProvider(null, null);

        $this->expectException(RuntimeException::class);
        $provider->getVehicle('AB12CDE');
    }
}
