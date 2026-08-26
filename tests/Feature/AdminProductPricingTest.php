<?php

namespace Tests\Feature;

use App\Models\ProductPrice;
use App\Models\User;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_access_the_pricing_screen(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.pricing.edit'))->assertForbidden();
    }

    public function test_the_pricing_screen_shows_the_current_prices(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.pricing.edit'))
            ->assertOk()
            ->assertSee('8.99')
            ->assertSee('11.99')
            ->assertSee('14.99');
    }

    public function test_an_admin_can_update_all_three_prices_and_it_takes_effect_immediately(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.pricing.update'), [
            'check' => '6.99',
            'plus' => '9.99',
            'rebuild' => '12.99',
        ])->assertRedirect(route('admin.pricing.edit'));

        $pricing = app(PricingService::class);
        $this->assertSame(6.99, $pricing->forCheck()->gross);
        $this->assertSame(9.99, $pricing->forPlus()->gross);
        $this->assertSame(12.99, $pricing->forRebuild()->gross);
    }

    public function test_invalid_prices_are_rejected_and_nothing_changes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $originalCheckPrice = ProductPrice::where('type', 'check')->value('gross');

        $this->actingAs($admin)->put(route('admin.pricing.update'), [
            'check' => '-5',
            'plus' => '9.99',
            'rebuild' => '12.99',
        ])->assertSessionHasErrors('check');

        $this->assertEquals($originalCheckPrice, ProductPrice::where('type', 'check')->value('gross'));
    }

    public function test_an_amended_price_appears_on_the_landing_page(): void
    {
        ProductPrice::where('type', 'check')->update(['gross' => 5.55]);

        $this->get('/')->assertSeeText('5.55');
    }
}
