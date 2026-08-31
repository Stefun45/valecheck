<?php

namespace Tests\Unit;

use App\Services\VehicleImagery\MockVehicleImageProvider;
use Tests\TestCase;

class MockVehicleImageProviderTest extends TestCase
{
    public function test_it_returns_a_valid_non_empty_png(): void
    {
        $result = (new MockVehicleImageProvider)->fetch('AB12CDE', 'Blue');

        $this->assertTrue($result->available);
        $this->assertSame('image/png', $result->mimeType);
        $this->assertNotNull($result->contents);
        $this->assertStringStartsWith("\x89PNG", $result->contents);
    }

    public function test_it_does_not_error_on_an_unrecognised_or_missing_colour(): void
    {
        $result = (new MockVehicleImageProvider)->fetch('AB12CDE', 'Chartreuse');
        $this->assertTrue($result->available);

        $resultNoColour = (new MockVehicleImageProvider)->fetch('AB12CDE', null);
        $this->assertTrue($resultNoColour->available);
    }
}
