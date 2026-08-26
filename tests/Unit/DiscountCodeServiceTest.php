<?php

namespace Tests\Unit;

use App\Models\DiscountCode;
use App\Services\Discounts\DiscountCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_a_valid_active_code(): void
    {
        DiscountCode::create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);

        $found = app(DiscountCodeService::class)->find('save10', 'check');

        $this->assertNotNull($found);
        $this->assertSame('SAVE10', $found->code);
    }

    public function test_it_rejects_an_unknown_code(): void
    {
        $this->assertNull(app(DiscountCodeService::class)->find('NOPE', 'check'));
    }

    public function test_it_rejects_an_inactive_code(): void
    {
        DiscountCode::create(['code' => 'OFF', 'type' => 'percentage', 'value' => 10, 'is_active' => false]);

        $this->assertNull(app(DiscountCodeService::class)->find('OFF', 'check'));
    }

    public function test_it_rejects_an_expired_code(): void
    {
        DiscountCode::create(['code' => 'OLD', 'type' => 'percentage', 'value' => 10, 'expires_at' => now()->subDay()]);

        $this->assertNull(app(DiscountCodeService::class)->find('OLD', 'check'));
    }

    public function test_it_rejects_a_code_that_has_reached_its_redemption_limit(): void
    {
        DiscountCode::create(['code' => 'MAXED', 'type' => 'percentage', 'value' => 10, 'max_redemptions' => 2, 'times_redeemed' => 2]);

        $this->assertNull(app(DiscountCodeService::class)->find('MAXED', 'check'));
    }

    public function test_it_rejects_a_code_that_does_not_apply_to_the_product(): void
    {
        DiscountCode::create(['code' => 'PLUSONLY', 'type' => 'percentage', 'value' => 10, 'applicable_products' => ['plus']]);

        $this->assertNull(app(DiscountCodeService::class)->find('PLUSONLY', 'check'));
        $this->assertNotNull(app(DiscountCodeService::class)->find('PLUSONLY', 'plus'));
    }

    public function test_apply_computes_a_percentage_discount(): void
    {
        $code = DiscountCode::create(['code' => 'SAVE20', 'type' => 'percentage', 'value' => 20]);

        $this->assertSame(8.0, app(DiscountCodeService::class)->apply($code, 10.0));
    }

    public function test_apply_computes_a_fixed_discount(): void
    {
        $code = DiscountCode::create(['code' => 'FIVEOFF', 'type' => 'fixed', 'value' => 5]);

        $this->assertSame(5.0, app(DiscountCodeService::class)->apply($code, 10.0));
    }

    public function test_apply_never_goes_negative(): void
    {
        $code = DiscountCode::create(['code' => 'BIGFIXED', 'type' => 'fixed', 'value' => 50]);

        $this->assertSame(0.0, app(DiscountCodeService::class)->apply($code, 10.0));
    }
}
