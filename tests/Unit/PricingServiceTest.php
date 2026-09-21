<?php

namespace Tests\Unit;

use App\Models\ProductPrice;
use App\Models\SitePromotion;
use App\Models\User;
use App\Models\UserProductPrice;
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

    public function test_subscription_plans_are_flat_prices_not_derived_from_plus(): void
    {
        $pricing = new PricingService;

        $this->assertSame(49.99, $pricing->forSubscription('trader')->gross);
        $this->assertSame(94.99, $pricing->forSubscription('pro')->gross);
        $this->assertSame(149.99, $pricing->forSubscription('dealer')->gross);

        ProductPrice::where('type', 'plus')->update(['gross' => 999.00]);

        $pricing = new PricingService;
        $this->assertSame(49.99, $pricing->forSubscription('trader')->gross);
        $this->assertSame(94.99, $pricing->forSubscription('pro')->gross);
        $this->assertSame(149.99, $pricing->forSubscription('dealer')->gross);
    }

    public function test_a_user_with_a_custom_price_pays_that_instead_of_the_standard_price(): void
    {
        $user = User::factory()->create();
        UserProductPrice::create(['user_id' => $user->id, 'type' => 'plus', 'gross' => 5.00]);

        $this->assertSame(5.00, (new PricingService)->forPlus($user)->gross);
    }

    public function test_a_user_with_no_custom_price_still_pays_standard_pricing(): void
    {
        $user = User::factory()->create();

        $this->assertSame((new PricingService)->forPlus()->gross, (new PricingService)->forPlus($user)->gross);
    }

    public function test_a_custom_price_is_scoped_to_one_product_type_only(): void
    {
        $user = User::factory()->create();
        UserProductPrice::create(['user_id' => $user->id, 'type' => 'plus', 'gross' => 5.00]);

        $pricing = new PricingService;

        $this->assertSame(5.00, $pricing->forPlus($user)->gross);
        $this->assertSame($pricing->forCheck()->gross, $pricing->forCheck($user)->gross);
    }

    public function test_a_custom_price_does_not_affect_what_other_users_pay(): void
    {
        $vip = User::factory()->create();
        $everyoneElse = User::factory()->create();
        UserProductPrice::create(['user_id' => $vip->id, 'type' => 'check', 'gross' => 1.00]);

        $pricing = new PricingService;

        $this->assertSame(1.00, $pricing->forCheck($vip)->gross);
        $this->assertSame(8.99, $pricing->forCheck($everyoneElse)->gross);
    }

    public function test_no_user_passed_at_all_behaves_exactly_as_before(): void
    {
        $this->assertSame(8.99, (new PricingService)->forProduct('check')->gross);
    }

    public function test_a_live_site_promotion_sets_the_explicit_discounted_price(): void
    {
        SitePromotion::current('check')->update(['is_active' => true, 'discounted_gross' => 7.19]);
        SitePromotion::current('plus')->update(['is_active' => true, 'discounted_gross' => 9.59]);

        $this->assertSame(7.19, (new PricingService)->forCheck()->gross);
        $this->assertSame(9.59, (new PricingService)->forPlus()->gross);
    }

    public function test_an_inactive_or_unset_promotion_changes_nothing(): void
    {
        SitePromotion::current('check')->update(['is_active' => false, 'discounted_gross' => 7.19]);
        $this->assertSame(8.99, (new PricingService)->forCheck()->gross);

        SitePromotion::current('check')->update(['is_active' => true, 'discounted_gross' => null]);
        $this->assertSame(8.99, (new PricingService)->forCheck()->gross);
    }

    public function test_a_live_promotion_never_discounts_a_bespoke_user_price_further(): void
    {
        $user = User::factory()->create();
        UserProductPrice::create(['user_id' => $user->id, 'type' => 'check', 'gross' => 5.00]);
        SitePromotion::current('check')->update(['is_active' => true, 'discounted_gross' => 4.00]);

        $this->assertSame(5.00, (new PricingService)->forCheck($user)->gross);
    }

    public function test_a_live_promotion_still_discounts_a_user_with_no_bespoke_price(): void
    {
        $user = User::factory()->create();
        SitePromotion::current('check')->update(['is_active' => true, 'discounted_gross' => 4.50]);

        $this->assertSame(4.50, (new PricingService)->forCheck($user)->gross);
    }

    public function test_standard_price_ignores_a_live_promotion_for_a_before_after_comparison(): void
    {
        SitePromotion::current('check')->update(['is_active' => true, 'discounted_gross' => 7.19]);

        $pricing = new PricingService;
        $this->assertSame(8.99, $pricing->standardPrice('check')->gross);
        $this->assertSame(7.19, $pricing->forCheck()->gross);
    }

    public function test_a_live_promotion_flows_through_to_credit_packs_via_the_plus_price(): void
    {
        SitePromotion::current('plus')->update(['is_active' => true, 'discounted_gross' => 9.59]);

        $this->assertSame(9.59, (new PricingService)->forCreditPack('plus_1')->gross);
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
