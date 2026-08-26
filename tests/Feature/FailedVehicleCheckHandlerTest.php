<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\SubscriptionUsage;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Pipeline\FailedVehicleCheckHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FailedVehicleCheckHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_failed_credit_funded_check_refunds_the_credit(): void
    {
        $user = User::factory()->create();

        $ledger = app(CreditLedgerService::class);
        $ledger->grantPurchasedCredits($user, VehicleCheck::TYPE_REBUILD, 2);
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_REBUILD,
            'funding_source' => 'credit',
        ]);
        $ledger->consumeCredit($user, VehicleCheck::TYPE_REBUILD, $check);

        $this->assertSame(1, $ledger->balance($user, VehicleCheck::TYPE_REBUILD));

        app(FailedVehicleCheckHandler::class)->handle($check->id, 'Anthropic is not configured.');

        $check->refresh();
        $this->assertSame(VehicleCheck::STATUS_FAILED, $check->status);
        $this->assertSame('Anthropic is not configured.', $check->failure_reason);
        $this->assertSame(2, $ledger->balance($user, VehicleCheck::TYPE_REBUILD));
    }

    public function test_a_failed_subscription_funded_check_releases_the_allowance(): void
    {
        $user = User::factory()->create();
        $usage = SubscriptionUsage::create([
            'user_id' => $user->id,
            'plan' => 'trader',
            'report_type' => VehicleCheck::TYPE_REBUILD,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'allowance' => 5,
            'used' => 1,
        ]);

        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_REBUILD,
            'funding_source' => 'subscription',
        ]);

        app(FailedVehicleCheckHandler::class)->handle($check->id, 'Provider timeout.');

        $this->assertSame(0, $usage->fresh()->used);
    }

    public function test_handling_an_already_failed_check_is_a_no_op(): void
    {
        $user = User::factory()->create();

        $ledger = app(CreditLedgerService::class);
        $ledger->grantPurchasedCredits($user, VehicleCheck::TYPE_REBUILD, 2);
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_REBUILD,
            'funding_source' => 'credit',
            'status' => VehicleCheck::STATUS_FAILED,
        ]);
        $ledger->consumeCredit($user, VehicleCheck::TYPE_REBUILD, $check);

        app(FailedVehicleCheckHandler::class)->handle($check->id, 'Second failure attempt.');

        // No refund should be issued for a check that was already marked failed.
        $this->assertSame(1, $ledger->balance($user, VehicleCheck::TYPE_REBUILD));
    }

    public function test_a_failed_purchase_funded_check_marks_the_payment_refunded(): void
    {
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'description' => 'ValeCheck Plus',
            'gross' => 11.99,
            'net' => 9.99,
            'vat' => 2.00,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PAID,
        ]);

        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'funding_source' => 'purchase',
            'payment_id' => $payment->id,
        ]);

        app(FailedVehicleCheckHandler::class)->handle($check->id, 'Vehicle data provider timeout.');

        $this->assertSame(Payment::STATUS_REFUNDED, $payment->fresh()->status);
    }

    public function test_vehicle_relation_is_untouched_on_failure(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'funding_source' => 'purchase',
        ]);

        app(FailedVehicleCheckHandler::class)->handle($check->id, 'History provider timeout.');

        $this->assertSame($vehicle->id, $check->fresh()->vehicle_id);
    }
}
