<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAdsConversionTest extends TestCase
{
    use RefreshDatabase;

    private function configureGoogleAds(): void
    {
        config([
            'valecheck.google_ads_id' => 'AW-625010447',
            'valecheck.google_ads_purchase_label' => '1rEACLjQ_vscEI_Og6oC',
        ]);
    }

    public function test_the_success_url_includes_the_real_payment_id(): void
    {
        // StripeCheckoutService itself is exercised elsewhere (mocked
        // Stripe API) - this just confirms the query string shape the
        // conversion partial depends on, via a real completed purchase.
        $user = User::factory()->create(['email_verified_at' => now()]);
        $payment = Payment::create([
            'user_id' => $user->id, 'type' => 'check', 'description' => 'Test',
            'gross' => 9.99, 'net' => 8.33, 'vat' => 1.66, 'vat_rate' => 0.20,
            'currency' => 'GBP', 'status' => Payment::STATUS_PAID,
        ]);
        $check = VehicleCheck::factory()->create(['user_id' => $user->id, 'payment_id' => $payment->id]);

        $this->configureGoogleAds();

        $this->actingAs($user)
            ->get(route('vehicle-checks.show', $check).'?paid=1&payment='.$payment->id)
            ->assertOk()
            ->assertSee('AW-625010447/1rEACLjQ_vscEI_Og6oC', false)
            ->assertSee("'value': 9.99", false)
            ->assertSee("'currency': 'GBP'", false)
            ->assertSee("'transaction_id': '{$payment->id}'", false);
    }

    public function test_no_conversion_fires_without_the_google_ads_config_set(): void
    {
        // The default state for every environment except production.
        config(['valecheck.google_ads_id' => null, 'valecheck.google_ads_purchase_label' => null]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $payment = Payment::create([
            'user_id' => $user->id, 'type' => 'check', 'description' => 'Test',
            'gross' => 9.99, 'net' => 8.33, 'vat' => 1.66, 'vat_rate' => 0.20,
            'currency' => 'GBP', 'status' => Payment::STATUS_PAID,
        ]);
        $check = VehicleCheck::factory()->create(['user_id' => $user->id, 'payment_id' => $payment->id]);

        $this->actingAs($user)
            ->get(route('vehicle-checks.show', $check).'?paid=1&payment='.$payment->id)
            ->assertOk()
            ->assertDontSee('gtag(\'event\', \'conversion\'', false);
    }

    public function test_no_conversion_fires_when_the_payment_is_not_actually_paid_yet(): void
    {
        // Covers the real race: Stripe's browser redirect can arrive
        // before the checkout.session.completed webhook does - the
        // conversion must not fire on an unconfirmed/pending payment.
        $this->configureGoogleAds();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $payment = Payment::create([
            'user_id' => $user->id, 'type' => 'check', 'description' => 'Test',
            'gross' => 9.99, 'net' => 8.33, 'vat' => 1.66, 'vat_rate' => 0.20,
            'currency' => 'GBP', 'status' => Payment::STATUS_PENDING,
        ]);
        $check = VehicleCheck::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('vehicle-checks.show', $check).'?paid=1&payment='.$payment->id)
            ->assertOk()
            ->assertDontSee('gtag(\'event\', \'conversion\'', false);
    }

    public function test_no_conversion_fires_on_a_plain_revisit_without_the_payment_query_string(): void
    {
        // The permanent "view my report" URL is the same one Stripe
        // redirects to - a later bookmarked revisit must never re-fire.
        $this->configureGoogleAds();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $payment = Payment::create([
            'user_id' => $user->id, 'type' => 'check', 'description' => 'Test',
            'gross' => 9.99, 'net' => 8.33, 'vat' => 1.66, 'vat_rate' => 0.20,
            'currency' => 'GBP', 'status' => Payment::STATUS_PAID,
        ]);
        $check = VehicleCheck::factory()->create(['user_id' => $user->id, 'payment_id' => $payment->id]);

        $this->actingAs($user)
            ->get(route('vehicle-checks.show', $check))
            ->assertOk()
            ->assertDontSee('gtag(\'event\', \'conversion\'', false);
    }

    public function test_dashboard_fires_the_conversion_for_a_credit_pack_purchase(): void
    {
        $this->configureGoogleAds();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $payment = Payment::create([
            'user_id' => $user->id, 'type' => Payment::TYPE_CREDIT_PACK, 'description' => 'Test',
            'gross' => 49.99, 'net' => 41.66, 'vat' => 8.33, 'vat_rate' => 0.20,
            'currency' => 'GBP', 'status' => Payment::STATUS_PAID,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard').'?paid=1&payment='.$payment->id)
            ->assertOk()
            ->assertSee("'value': 49.99", false)
            ->assertSee("'transaction_id': '{$payment->id}'", false);
    }
}
