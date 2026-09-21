<?php

namespace Tests\Unit;

use App\Models\DiscountCode;
use App\Models\Payment;
use App\Models\SitePromotion;
use App\Models\User;
use App\Models\UserProductPrice;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Throwable;

class StripeCheckoutServiceCustomPriceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Stripe isn't configured in the test environment (the real controller
     * guards on this and shows a pending page instead - see
     * VehicleCheckCheckoutController), so calling the service directly
     * throws once it reaches the actual Stripe API call. The Payment row
     * this test cares about is created just before that, so the price
     * that was actually going to be charged is still verifiable.
     */
    public function test_a_check_belonging_to_a_user_with_a_custom_price_is_charged_that_price_not_the_standard_one(): void
    {
        $user = User::factory()->create();
        UserProductPrice::create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_CHECK, 'gross' => 3.50]);
        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_PENDING,
        ]);

        try {
            app(StripeCheckoutService::class)->checkoutForVehicleCheck($check);
        } catch (Throwable) {
            // Expected - no real Stripe key in this environment.
        }

        $payment = Payment::where('user_id', $user->id)->firstOrFail();
        $this->assertEqualsWithDelta(3.50, (float) $payment->gross, 0.001);
    }

    public function test_a_check_belonging_to_a_user_with_no_custom_price_is_charged_the_standard_price(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_PENDING,
        ]);

        try {
            app(StripeCheckoutService::class)->checkoutForVehicleCheck($check);
        } catch (Throwable) {
            // Expected - no real Stripe key in this environment.
        }

        $payment = Payment::where('user_id', $user->id)->firstOrFail();
        $this->assertEqualsWithDelta(8.99, (float) $payment->gross, 0.001);
    }

    public function test_a_discount_code_is_ignored_at_the_real_charge_point_for_a_custom_priced_account(): void
    {
        // The two discount mechanisms must never compound: a discount
        // code's reduction must not be taken off an already-reduced
        // custom price.
        $user = User::factory()->create();
        UserProductPrice::create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_CHECK, 'gross' => 3.50]);
        DiscountCode::create(['code' => 'STACK', 'type' => 'percentage', 'value' => 50]);
        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_PENDING,
            'discount_code' => 'STACK',
        ]);

        try {
            app(StripeCheckoutService::class)->checkoutForVehicleCheck($check);
        } catch (Throwable) {
            // Expected - no real Stripe key in this environment.
        }

        $payment = Payment::where('user_id', $user->id)->firstOrFail();
        // Full £3.50 custom price charged - the code was rejected, not
        // stacked on top of it.
        $this->assertEqualsWithDelta(3.50, (float) $payment->gross, 0.001);
        $this->assertNull($payment->discount_code_id);
    }

    public function test_a_live_site_promotion_is_actually_charged_and_the_true_standard_price_is_recorded(): void
    {
        SitePromotion::current()->update(['is_active' => true, 'percentage' => 20]);
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_PENDING,
        ]);

        try {
            app(StripeCheckoutService::class)->checkoutForVehicleCheck($check);
        } catch (Throwable) {
            // Expected - no real Stripe key in this environment.
        }

        $payment = Payment::where('user_id', $user->id)->firstOrFail();
        $this->assertEqualsWithDelta(7.19, (float) $payment->gross, 0.001);
        // original_gross records the true £8.99 standard price, not just
        // whatever a discount code might have reduced it from - it must
        // reflect the promotion even though no code was ever involved.
        $this->assertEqualsWithDelta(8.99, (float) $payment->original_gross, 0.001);
    }

    public function test_a_bespoke_user_price_leaves_original_gross_null_even_with_a_live_promotion(): void
    {
        // The custom price itself is what's charged and recorded as-is -
        // it was never discounted further, so there's nothing to compare
        // it against.
        SitePromotion::current()->update(['is_active' => true, 'percentage' => 20]);
        $user = User::factory()->create();
        UserProductPrice::create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_CHECK, 'gross' => 3.50]);
        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_PENDING,
        ]);

        try {
            app(StripeCheckoutService::class)->checkoutForVehicleCheck($check);
        } catch (Throwable) {
            // Expected - no real Stripe key in this environment.
        }

        $payment = Payment::where('user_id', $user->id)->firstOrFail();
        $this->assertEqualsWithDelta(3.50, (float) $payment->gross, 0.001);
        $this->assertNull($payment->original_gross);
    }

    public function test_no_promotion_and_no_discount_leaves_original_gross_null(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_PENDING,
        ]);

        try {
            app(StripeCheckoutService::class)->checkoutForVehicleCheck($check);
        } catch (Throwable) {
            // Expected - no real Stripe key in this environment.
        }

        $payment = Payment::where('user_id', $user->id)->firstOrFail();
        $this->assertNull($payment->original_gross);
    }
}
