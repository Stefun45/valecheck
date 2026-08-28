<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use App\Services\Pipeline\FailedVehicleCheckUpgradeHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCheckUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private function completedCheck(): VehicleCheck
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['registration' => 'AB12CDE']);
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'funding_source' => 'purchase',
            'registration' => 'AB12CDE',
        ]);

        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false]);

        return $check;
    }

    public function test_a_completed_check_is_upgradeable_but_a_plus_or_pending_check_is_not(): void
    {
        $check = $this->completedCheck();
        $this->assertTrue($check->isUpgradeable());

        $check->update(['type' => VehicleCheck::TYPE_PLUS]);
        $this->assertFalse($check->fresh()->isUpgradeable());

        $check->update(['type' => VehicleCheck::TYPE_CHECK, 'status' => VehicleCheck::STATUS_PENDING]);
        $this->assertFalse($check->fresh()->isUpgradeable());
    }

    public function test_the_upgrade_checkout_route_is_forbidden_for_another_users_check(): void
    {
        $check = $this->completedCheck();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('checkout.vehicle-check.upgrade', $check))
            ->assertForbidden();
    }

    public function test_the_upgrade_checkout_route_404s_for_a_non_upgradeable_check(): void
    {
        $check = $this->completedCheck();
        $check->update(['type' => VehicleCheck::TYPE_PLUS]);

        $this->actingAs($check->user)
            ->get(route('checkout.vehicle-check.upgrade', $check))
            ->assertNotFound();
    }

    public function test_the_upgrade_checkout_route_shows_the_pending_page_when_stripe_is_not_configured(): void
    {
        $check = $this->completedCheck();

        $this->actingAs($check->user)
            ->get(route('checkout.vehicle-check.upgrade', $check))
            ->assertOk()
            ->assertSeeText("Vehicle check #{$check->id}");
    }

    public function test_a_paid_upgrade_session_flips_the_check_to_plus_and_runs_only_the_plus_exclusive_jobs(): void
    {
        $check = $this->completedCheck();

        $payment = Payment::create([
            'user_id' => $check->user_id,
            'type' => Payment::TYPE_PLUS_UPGRADE,
            'description' => 'Upgrade to ValeCheck Plus — AB12CDE',
            'gross' => 3.50,
            'net' => 2.92,
            'vat' => 0.58,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PENDING,
        ]);

        app(StripeCheckoutCompletionHandler::class)->handle([
            'id' => 'cs_test_upgrade',
            'payment_intent' => 'pi_test_upgrade',
            'payment_status' => 'paid',
            'metadata' => [
                'kind' => 'vehicle_check_upgrade',
                'vehicle_check_id' => (string) $check->id,
                'payment_id' => (string) $payment->id,
            ],
        ]);

        $check->refresh();

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(VehicleCheck::TYPE_PLUS, $check->type);
        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertSame($payment->id, $check->upgrade_payment_id);
        $this->assertNotNull($check->upgraded_at);
        $this->assertNotNull($check->valuation);
        $this->assertNotNull($check->salvageAuctionCheck);
        $this->assertNotNull($check->taxCost);
    }

    public function test_an_already_upgraded_check_ignores_a_duplicate_webhook_delivery(): void
    {
        $check = $this->completedCheck();
        $check->update([
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'upgraded_at' => now(),
        ]);

        $payment = Payment::create([
            'user_id' => $check->user_id,
            'type' => Payment::TYPE_PLUS_UPGRADE,
            'description' => 'Upgrade to ValeCheck Plus — AB12CDE',
            'gross' => 3.50,
            'net' => 2.92,
            'vat' => 0.58,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PENDING,
        ]);

        app(StripeCheckoutCompletionHandler::class)->handle([
            'id' => 'cs_test_dup',
            'payment_status' => 'paid',
            'metadata' => [
                'kind' => 'vehicle_check_upgrade',
                'vehicle_check_id' => (string) $check->id,
                'payment_id' => (string) $payment->id,
            ],
        ]);

        $this->assertNull($check->fresh()->taxCost);
    }

    public function test_a_failed_upgrade_reverts_to_the_original_completed_check_report(): void
    {
        // Mirrors FailedVehicleCheckHandlerTest's convention of exercising
        // the handler directly — Bus::chain's ->catch() callback is only
        // ever invoked by real queue-worker failure handling, not by the
        // 'sync' driver used in tests, so there's no way to trigger it via
        // a genuinely thrown job exception here.
        $check = $this->completedCheck();
        $check->update([
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_PROCESSING,
        ]);

        $payment = Payment::create([
            'user_id' => $check->user_id,
            'type' => Payment::TYPE_PLUS_UPGRADE,
            'description' => 'Upgrade to ValeCheck Plus — AB12CDE',
            'gross' => 3.50,
            'net' => 2.92,
            'vat' => 0.58,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PAID,
        ]);
        $check->update(['upgrade_payment_id' => $payment->id, 'upgraded_at' => now()]);

        app(FailedVehicleCheckUpgradeHandler::class)->handle($check->id, 'Simulated valuation failure.');

        $check->refresh();

        $this->assertSame(VehicleCheck::TYPE_CHECK, $check->type);
        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertSame('Simulated valuation failure.', $check->failure_reason);
        $this->assertNull($check->upgrade_payment_id);
        $this->assertNull($check->upgraded_at);
        $this->assertSame(Payment::STATUS_REFUNDED, $payment->fresh()->status);
    }

    public function test_handling_an_already_completed_check_is_a_no_op(): void
    {
        $check = $this->completedCheck();

        app(FailedVehicleCheckUpgradeHandler::class)->handle($check->id, 'Should never apply.');

        $this->assertNull($check->fresh()->failure_reason);
    }
}
