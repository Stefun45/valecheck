<?php

namespace Tests\Unit;

use App\Models\DiscountCode;
use App\Models\DiscountCodeRedemption;
use App\Models\SitePromotion;
use App\Models\User;
use App\Models\UserProductPrice;
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

    public function test_it_rejects_a_code_this_user_has_already_used_up_to_their_personal_limit(): void
    {
        $user = User::factory()->create();
        $code = DiscountCode::create(['code' => 'ONEEACH', 'type' => 'percentage', 'value' => 10, 'max_uses_per_user' => 1]);

        DiscountCodeRedemption::create(['discount_code_id' => $code->id, 'user_id' => $user->id, 'amount_discounted' => 1]);

        $this->assertNull(app(DiscountCodeService::class)->find('ONEEACH', 'check', $user));
    }

    public function test_a_per_user_limit_does_not_block_a_different_user(): void
    {
        $usedUp = User::factory()->create();
        $freshUser = User::factory()->create();
        $code = DiscountCode::create(['code' => 'ONEEACH2', 'type' => 'percentage', 'value' => 10, 'max_uses_per_user' => 1]);

        DiscountCodeRedemption::create(['discount_code_id' => $code->id, 'user_id' => $usedUp->id, 'amount_discounted' => 1]);

        $this->assertNull(app(DiscountCodeService::class)->find('ONEEACH2', 'check', $usedUp));
        $this->assertNotNull(app(DiscountCodeService::class)->find('ONEEACH2', 'check', $freshUser));
    }

    public function test_a_per_user_limit_is_not_checked_for_a_guest_with_no_user_yet(): void
    {
        // The pre-checkout preview can run before login — the per-user
        // limit is re-checked for real once a user exists, at Stripe
        // checkout time, which never trusts this client-side preview.
        DiscountCode::create(['code' => 'ONEEACH3', 'type' => 'percentage', 'value' => 10, 'max_uses_per_user' => 1]);

        $this->assertNotNull(app(DiscountCodeService::class)->find('ONEEACH3', 'check', null));
    }

    public function test_a_code_is_rejected_for_a_user_with_a_custom_price_for_this_product(): void
    {
        $user = User::factory()->create();
        UserProductPrice::create(['user_id' => $user->id, 'type' => 'check', 'gross' => 3.50]);
        DiscountCode::create(['code' => 'STACK', 'type' => 'percentage', 'value' => 10]);

        $this->assertNull(app(DiscountCodeService::class)->find('STACK', 'check', $user));
    }

    public function test_a_code_is_rejected_outright_while_a_site_wide_promotion_is_live(): void
    {
        // Everyone already gets the automatic discount - a typed code
        // stacking on top of it would compound two mechanisms never
        // meant to combine, the same reason a custom price blocks one.
        DiscountCode::create(['code' => 'STACK2', 'type' => 'percentage', 'value' => 10]);
        SitePromotion::current()->update(['is_active' => true, 'percentage' => 20]);

        $this->assertNull(app(DiscountCodeService::class)->find('STACK2', 'check'));
    }

    public function test_a_code_works_again_once_the_promotion_is_switched_off(): void
    {
        DiscountCode::create(['code' => 'BACKON', 'type' => 'percentage', 'value' => 10]);
        SitePromotion::current()->update(['is_active' => true, 'percentage' => 20]);
        $this->assertNull(app(DiscountCodeService::class)->find('BACKON', 'check'));

        SitePromotion::current()->update(['is_active' => false]);
        $this->assertNotNull(app(DiscountCodeService::class)->find('BACKON', 'check'));
    }

    public function test_a_custom_price_for_a_different_product_does_not_block_a_code_for_this_one(): void
    {
        $user = User::factory()->create();
        UserProductPrice::create(['user_id' => $user->id, 'type' => 'plus', 'gross' => 5.00]);
        DiscountCode::create(['code' => 'NOSTACK', 'type' => 'percentage', 'value' => 10]);

        $this->assertNotNull(app(DiscountCodeService::class)->find('NOSTACK', 'check', $user));
    }

    public function test_a_code_is_still_usable_by_a_user_with_no_custom_price_at_all(): void
    {
        $user = User::factory()->create();
        DiscountCode::create(['code' => 'FINE10', 'type' => 'percentage', 'value' => 10]);

        $this->assertNotNull(app(DiscountCodeService::class)->find('FINE10', 'check', $user));
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
