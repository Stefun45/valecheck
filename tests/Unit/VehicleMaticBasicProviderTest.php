<?php

namespace Tests\Unit;

use App\Services\RegistrationLookup\VehicleMaticBasicProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class VehicleMaticBasicProviderTest extends TestCase
{
    public function test_successful_lookup_maps_the_documented_response_fields(): void
    {
        Http::fake([
            'vehiclematic.com/*' => Http::response([
                'data' => [
                    'vrm' => 'AB12CDE',
                    'registration_number' => 'AB12CDE',
                    'make' => 'FORD',
                    'model' => 'FOCUS',
                    'colour' => 'BLUE',
                    'fuel_type' => 'PETROL',
                    'engine_capacity' => 1500,
                    'year_of_manufacture' => 2018,
                    'tax_status' => 'Taxed',
                    'mot_status' => 'Valid',
                ],
                'credit_balance' => 4821,
            ], 200),
        ]);

        $provider = new VehicleMaticBasicProvider('test-key', 'https://vehiclematic.com/products/vehicle-details/api/live');
        $result = $provider->preview('AB12 CDE');

        $this->assertSame('AB12CDE', $result->registration);
        $this->assertSame('FORD', $result->make);
        $this->assertSame('FOCUS', $result->model);
        $this->assertSame(2018, $result->yearOfManufacture);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://vehiclematic.com/products/vehicle-details/api/live/AB12CDE'
                && $request->method() === 'GET'
                && $request->hasHeader('X-VEHICLEMATIC-KEY', 'test-key');
        });
    }

    public function test_404_returns_null_rather_than_throwing(): void
    {
        Http::fake(['vehiclematic.com/*' => Http::response([], 404)]);

        $provider = new VehicleMaticBasicProvider('test-key', 'https://vehiclematic.com/products/vehicle-details/api/live');

        $this->assertNull($provider->preview('ZZ99ZZZ'));
    }

    public function test_insufficient_credits_throws(): void
    {
        Http::fake(['vehiclematic.com/*' => Http::response([], 402)]);

        $provider = new VehicleMaticBasicProvider('test-key', 'https://vehiclematic.com/products/vehicle-details/api/live');

        $this->expectException(RuntimeException::class);
        $provider->preview('AB12CDE');
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

        $provider = new VehicleMaticBasicProvider('bad-key', 'https://vehiclematic.com/products/vehicle-details/api/live');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The X-VEHICLEMATIC-KEY provided is not valid.');
        $provider->preview('AB12CDE');
    }
}
