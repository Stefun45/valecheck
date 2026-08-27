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
}
