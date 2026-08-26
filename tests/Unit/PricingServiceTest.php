<?php

namespace Tests\Unit;

use App\Models\ProductPrice;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_price_breaks_down_vat_correctly(): void
    {
        $breakdown = (new PricingService)->forCheck();

        $this->assertSame(8.99, $breakdown->gross);
        $this->assertSame(7.49, $breakdown->net);
        $this->assertSame(1.5, $breakdown->vat);
        $this->assertSame(0.20, $breakdown->vatRate);
        $this->assertSame('GBP', $breakdown->currency);
    }

    public function test_plus_price_breaks_down_vat_correctly(): void
    {
        $breakdown = (new PricingService)->forPlus();

        $this->assertSame(11.99, $breakdown->gross);
        $this->assertSame(9.99, $breakdown->net);
        $this->assertSame(2.0, $breakdown->vat);
    }

    public function test_rebuild_price_breaks_down_vat_correctly(): void
    {
        $breakdown = (new PricingService)->forRebuild();

        $this->assertSame(14.99, $breakdown->gross);
        $this->assertSame(12.49, $breakdown->net);
        $this->assertSame(2.5, $breakdown->vat);
    }

    public function test_for_product_resolves_by_type_key(): void
    {
        $pricing = new PricingService;

        $this->assertSame($pricing->forCheck()->gross, $pricing->forProduct('check')->gross);
        $this->assertSame($pricing->forPlus()->gross, $pricing->forProduct('plus')->gross);
        $this->assertSame($pricing->forRebuild()->gross, $pricing->forProduct('rebuild')->gross);
    }

    public function test_an_admin_amended_price_is_reflected_immediately(): void
    {
        ProductPrice::where('type', 'check')->update(['gross' => 6.49]);

        $this->assertSame(6.49, (new PricingService)->forCheck()->gross);
    }

    public function test_credit_packs_are_priced_off_the_plus_price_with_a_bulk_discount(): void
    {
        $pricing = new PricingService;
        $plusGross = $pricing->forPlus()->gross;

        $this->assertSame($plusGross, $pricing->forCreditPack('plus_1')->gross);
        $this->assertSame(round($plusGross * 5 * 0.90, 2), $pricing->forCreditPack('plus_5')->gross);
        $this->assertSame(round($plusGross * 10 * 0.85, 2), $pricing->forCreditPack('plus_10')->gross);
    }

    public function test_credit_pack_price_moves_with_an_admin_amended_plus_price(): void
    {
        ProductPrice::where('type', 'plus')->update(['gross' => 10.00]);

        $this->assertSame(10.00, (new PricingService)->forCreditPack('plus_1')->gross);
    }

    public function test_trader_and_pro_subscriptions_are_priced_off_the_plus_price_with_a_discount(): void
    {
        $pricing = new PricingService;
        $plusGross = $pricing->forPlus()->gross;

        $this->assertSame(round($plusGross * 5 * 0.85, 2), $pricing->forSubscription('trader')->gross);
        $this->assertSame(round($plusGross * 10 * 0.80, 2), $pricing->forSubscription('pro')->gross);
    }

    public function test_dealer_subscription_is_a_flat_price_not_derived_from_plus(): void
    {
        $this->assertSame(149.99, (new PricingService)->forSubscription('dealer')->gross);

        ProductPrice::where('type', 'plus')->update(['gross' => 999.00]);

        $this->assertSame(149.99, (new PricingService)->forSubscription('dealer')->gross);
    }

    public function test_gross_always_equals_net_plus_vat(): void
    {
        $pricing = new PricingService;

        foreach ([8.99, 11.99, 14.99, 39.99, 59.99, 99.99, 129.99] as $gross) {
            $breakdown = $pricing->breakdown($gross);

            $this->assertEqualsWithDelta($breakdown->gross, $breakdown->net + $breakdown->vat, 0.01);
        }
    }
}
