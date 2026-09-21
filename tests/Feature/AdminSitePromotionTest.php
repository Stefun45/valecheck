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

    public function test_a_non_admin_cannot_access_the_promotion_screen(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.promotion.edit'))->assertForbidden();
    }

    public function test_the_promotion_screen_shows_the_current_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        SitePromotion::current()->update(['is_active' => true, 'percentage' => 15, 'label' => 'Spring Sale']);

        $this->actingAs($admin)
            ->get(route('admin.promotion.edit'))
            ->assertOk()
            ->assertSee('15')
            ->assertSee('Spring Sale');
    }

    public function test_an_admin_can_turn_the_promotion_on_and_it_takes_effect_immediately(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.promotion.update'), [
            'is_active' => '1',
            'percentage' => '20',
            'label' => 'Launch Offer',
        ])->assertRedirect(route('admin.promotion.edit'));

        $this->assertSame(7.19, app(PricingService::class)->forCheck()->gross);
    }

    public function test_an_admin_can_turn_the_promotion_off_and_standard_pricing_returns(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        SitePromotion::current()->update(['is_active' => true, 'percentage' => 20]);

        $this->actingAs($admin)->put(route('admin.promotion.update'), [
            'is_active' => '0',
            'percentage' => '20',
            'label' => 'Launch Offer',
        ]);

        $this->assertSame(8.99, app(PricingService::class)->forCheck()->gross);
    }

    public function test_a_percentage_of_100_or_more_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.promotion.update'), [
            'is_active' => '1',
            'percentage' => '100',
            'label' => 'Launch Offer',
        ])->assertSessionHasErrors('percentage');

        $this->assertFalse(SitePromotion::current()->fresh()->isLive());
    }

    public function test_a_live_promotion_shows_a_struck_through_price_on_the_landing_page(): void
    {
        SitePromotion::current()->update(['is_active' => true, 'percentage' => 20, 'label' => 'Launch Offer']);

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
