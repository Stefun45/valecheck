<?php

namespace Tests\Unit;

use App\Services\RegistrationLookup\DvlaVesProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class DvlaVesProviderTest extends TestCase
{
    public function test_successful_lookup_maps_the_documented_response_fields(): void
    {
        Http::fake([
            'driver-vehicle-licensing.api.gov.uk/*' => Http::response([
                'registrationNumber' => 'AB12CDE',
                'make' => 'FORD',
                'colour' => 'BLUE',
                'fuelType' => 'PETROL',
                'yearOfManufacture' => 2018,
                'engineCapacity' => 1500,
                'motStatus' => 'Valid',
                'taxStatus' => 'Taxed',
            ], 200),
        ]);

        $provider = new DvlaVesProvider('test-key', 'https://driver-vehicle-licensing.api.gov.uk');
        $result = $provider->preview('AB12 CDE');

        $this->assertSame('AB12CDE', $result->registration);
        $this->assertSame('FORD', $result->make);
        $this->assertSame('BLUE', $result->colour);
        $this->assertSame(2018, $result->yearOfManufacture);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://driver-vehicle-licensing.api.gov.uk/vehicle-enquiry/v1/vehicles'
                && $request->method() === 'POST'
                && $request['registrationNumber'] === 'AB12CDE'
                && $request->hasHeader('x-api-key', 'test-key');
        });
    }

    public function test_404_returns_null_rather_than_throwing(): void
    {
        Http::fake([
            'driver-vehicle-licensing.api.gov.uk/*' => Http::response([], 404),
        ]);

        $provider = new DvlaVesProvider('test-key', 'https://driver-vehicle-licensing.api.gov.uk');

        $this->assertNull($provider->preview('ZZ99ZZZ'));
    }

    public function test_server_error_throws(): void
    {
        Http::fake([
            'driver-vehicle-licensing.api.gov.uk/*' => Http::response([], 503),
        ]);

        $provider = new DvlaVesProvider('test-key', 'https://driver-vehicle-licensing.api.gov.uk');

        $this->expectException(RuntimeException::class);
        $provider->preview('AB12CDE');
    }

    public function test_missing_configuration_throws(): void
    {
        $provider = new DvlaVesProvider(null, null);

        $this->expectException(RuntimeException::class);
        $provider->preview('AB12CDE');
    }
}
