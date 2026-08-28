<?php

namespace Tests\Unit;

use App\Services\VehicleTax\MockVehicleTaxCostProvider;
use Tests\TestCase;

class MockVehicleTaxCostProviderTest extends TestCase
{
    public function test_it_is_deterministic_for_the_same_registration(): void
    {
        $provider = new MockVehicleTaxCostProvider;

        $first = $provider->check('AB12CDE');
        $second = $provider->check('AB12CDE');

        $this->assertSame($first->available, $second->available);
        $this->assertSame($first->annualRate, $second->annualRate);
    }

    public function test_an_available_result_has_a_six_month_rate_less_than_the_annual_rate(): void
    {
        $provider = new MockVehicleTaxCostProvider;

        $result = $provider->check('AB12CDE');

        $this->assertTrue($result->available);
        $this->assertNotNull($result->annualRate);
        $this->assertLessThan($result->annualRate, $result->sixMonthRate);
    }

    public function test_some_registrations_are_unavailable(): void
    {
        $provider = new MockVehicleTaxCostProvider;

        $result = $provider->check('MN78OPQ');

        $this->assertFalse($result->available);
        $this->assertNull($result->annualRate);
    }
}
