<?php

namespace Tests\Unit;

use App\Services\Valuation\SalvageValuationService;
use Tests\TestCase;

class SalvageValuationServiceTest extends TestCase
{
    private SalvageValuationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SalvageValuationService;
    }

    public function test_no_write_off_history_uses_clean_value_unadjusted(): void
    {
        $result = $this->service->valuate(10000, null);

        $this->assertSame(10000.0, $result->adjustedValue);
        $this->assertSame(0.0, $result->discountApplied);
    }

    public function test_category_n_applies_the_configured_discount(): void
    {
        $result = $this->service->valuate(10000, 'N');

        $this->assertSame(0.25, $result->discountApplied);
        $this->assertSame(7500.0, $result->adjustedValue);
    }

    public function test_category_s_applies_the_configured_discount(): void
    {
        $result = $this->service->valuate(10000, 'S');

        $this->assertSame(0.35, $result->discountApplied);
        $this->assertSame(6500.0, $result->adjustedValue);
    }

    public function test_category_a_does_not_attempt_a_repaired_value(): void
    {
        $result = $this->service->valuate(10000, 'A');

        $this->assertNull($result->adjustedValue);
        $this->assertNull($result->discountApplied);
    }
}
