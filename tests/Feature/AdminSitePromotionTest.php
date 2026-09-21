<?php

namespace Tests\Feature;

use App\Models\SitePromotion;
use App\Models\User;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSitePromotionTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'promotions' => [
                'check' => ['is_active' => '0', 'discounted_gross' => null],
                'plus' => ['is_active' => '0', 'discounted_gross' => null],
                'rebuild' => ['is_active' => '0', 'discounted_gross' => null],
                'plus_upgrade' => ['is_active' => '0', 'discounted_gross' => null],
            ],
        ], $overrides);
    }

    public function test_a_non_admin_cannot_access_the_promotion_screen(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.promotion.edit'))->assertForbidden();
    }

    public function test_the_promotion_screen_shows_the_standard_and_current_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        SitePromotion::current('check')->update(['is_active' => true, 'discounted_gross' => 7.19]);

        $this->actingAs($admin)
            ->get(route('admin.promotion.edit'))
            ->assertOk()
            ->assertSee('8.99')
            ->assertSee('7.19');
    }

    public function test_an_admin_can_turn_a_plans_promotion_on_and_it_takes_effect_immediately(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.promotion.update'), $this->validPayload([
            'promotions' => ['check' => ['is_active' => '1', 'discounted_gross' => '7.19']],
        ]))->assertRedirect(route('admin.promotion.edit'));

        $this->assertSame(7.19, app(PricingService::class)->forCheck()->gross);
        // Plus was left untouched, at its own default (inactive) row.
        $this->assertSame(11.99, app(PricingService::class)->forPlus()->gross);
    }

    public function test_an_admin_can_turn_a_promotion_off_and_standard_pricing_returns(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        SitePromotion::current('check')->update(['is_active' => true, 'discounted_gross' => 7.19]);

        $this->actingAs($admin)->put(route('admin.promotion.update'), $this->validPayload([
            'promotions' => ['check' => ['is_active' => '0', 'discounted_gross' => '7.19']],
        ]));

        $this->assertSame(8.99, app(PricingService::class)->forCheck()->gross);
    }

    public function test_a_discounted_price_that_is_not_actually_lower_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.promotion.update'), $this->validPayload([
            'promotions' => ['check' => ['is_active' => '1', 'discounted_gross' => '8.99']],
        ]))->assertSessionHasErrors('promotions.check.discounted_gross');

        $this->assertFalse(SitePromotion::current('check')->fresh()->isLive());
    }

    public function test_a_discounted_price_higher_than_standard_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.promotion.update'), $this->validPayload([
            'promotions' => ['check' => ['is_active' => '1', 'discounted_gross' => '20.00']],
        ]))->assertSessionHasErrors('promotions.check.discounted_gross');
    }

    public function test_each_plan_is_independently_toggleable(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.promotion.update'), $this->validPayload([
            'promotions' => [
                'check' => ['is_active' => '1', 'discounted_gross' => '7.19'],
                'plus' => ['is_active' => '0', 'discounted_gross' => null],
            ],
        ]));

        $pricing = app(PricingService::class);
        $this->assertSame(7.19, $pricing->forCheck()->gross);
        $this->assertSame(11.99, $pricing->forPlus()->gross);
        $this->assertSame(14.99, $pricing->forRebuild()->gross);
    }

    public function test_a_live_promotion_shows_a_struck_through_price_and_banner_on_the_landing_page(): void
    {
        SitePromotion::current('check')->update(['is_active' => true, 'discounted_gross' => 7.19]);

        $this->get('/')
            ->assertSeeText('Launch Offer')
            ->assertSeeText('7.19')
            ->assertSeeText('8.99');
    }

    public function test_no_active_promotion_shows_no_banner_and_no_struck_through_price(): void
    {
        $this->get('/')
            ->assertDontSeeText('Launch Offer')
            ->assertSeeText('8.99');
    }
}
