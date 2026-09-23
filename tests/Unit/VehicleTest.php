<?php

namespace Tests\Unit;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_masked_vin_only_reveals_the_last_five_characters(): void
    {
        $vehicle = Vehicle::factory()->create(['vin' => 'WVWZZZ1JZXW000001']);

        $masked = $vehicle->maskedVin();

        $this->assertStringEndsWith('00001', $masked);
        $this->assertStringNotContainsString('WVWZZZ1JZXW', $masked);
    }

    public function test_masked_vin_is_null_when_there_is_no_vin(): void
    {
        $vehicle = Vehicle::factory()->create(['vin' => null]);

        $this->assertNull($vehicle->maskedVin());
    }

    public function test_a_real_vin_is_verifiable(): void
    {
        // Genuinely contains a single "X" (JZXW) - confirms the masked-VIN
        // detection below can't false-positive on an ordinary real VIN.
        $vehicle = Vehicle::factory()->create(['vin' => 'WVWZZZ1JZXW000001']);

        $this->assertTrue($vehicle->hasVerifiableVin());
    }

    public function test_a_vin_masked_by_the_provider_is_not_verifiable(): void
    {
        // One Auto's own masking format: 12 X's then the real last 5
        // characters - confirmed across every VIN this account has ever
        // received from them.
        $vehicle = Vehicle::factory()->create(['vin' => 'XXXXXXXXXXXX17837']);

        $this->assertFalse($vehicle->hasVerifiableVin());
    }

    public function test_no_vin_at_all_is_not_verifiable(): void
    {
        $vehicle = Vehicle::factory()->create(['vin' => null]);

        $this->assertFalse($vehicle->hasVerifiableVin());
    }
}
