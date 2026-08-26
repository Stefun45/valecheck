<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeCheckoutCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_vehicle_check_session_marks_payment_paid_and_dispatches_pipeline(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['registration' => 'AB12CDE']);
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_PENDING,
            'funding_source' => 'purchase',
            'registration' => 'AB12CDE',
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => 'check',
            'description' => 'ValeCheck Check',
            'gross' => 8.99,
            'net' => 7.49,
            'vat' => 1.50,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PENDING,
        ]);

        app(StripeCheckoutCompletionHandler::class)->handle([
            'id' => 'cs_test_123',
            'payment_intent' => 'pi_test_123',
            'payment_status' => 'paid',
            'metadata' => [
                'kind' => 'vehicle_check',
                'vehicle_check_id' => (string) $check->id,
                'payment_id' => (string) $payment->id,
            ],
        ]);

        $payment->refresh();
        $check->refresh();

        $this->assertSame(Payment::STATUS_PAID, $payment->status);
        $this->assertSame('cs_test_123', $payment->stripe_checkout_session_id);
        $this->assertSame($payment->id, $check->payment_id);
        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
    }

    public function test_paid_credit_pack_session_grants_purchased_credits(): void
    {
        $user = User::factory()->create();

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => Payment::TYPE_CREDIT_PACK,
            'description' => '5 Rebuild Reports',
            'gross' => 59.99,
            'net' => 49.99,
            'vat' => 10.00,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PENDING,
        ]);

        app(StripeCheckoutCompletionHandler::class)->handle([
            'id' => 'cs_test_456',
            'payment_status' => 'paid',
            'metadata' => [
                'kind' => 'credit_pack',
                'payment_id' => (string) $payment->id,
                'report_type' => 'rebuild',
                'credits' => '5',
            ],
        ]);

        $ledger = app(CreditLedgerService::class);

        $this->assertSame(5, $ledger->balance($user, 'rebuild'));
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
    }

    public function test_unpaid_session_is_ignored(): void
    {
        $user = User::factory()->create();

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => Payment::TYPE_CREDIT_PACK,
            'description' => '1 Rebuild Report',
            'gross' => 14.99,
            'net' => 12.49,
            'vat' => 2.50,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PENDING,
        ]);

        app(StripeCheckoutCompletionHandler::class)->handle([
            'id' => 'cs_test_789',
            'payment_status' => 'unpaid',
            'metadata' => [
                'kind' => 'credit_pack',
                'payment_id' => (string) $payment->id,
                'report_type' => 'rebuild',
                'credits' => '1',
            ],
        ]);

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
    }

    public function test_completion_is_idempotent_for_an_already_paid_payment(): void
    {
        $user = User::factory()->create();

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => Payment::TYPE_CREDIT_PACK,
            'description' => '1 Rebuild Report',
            'gross' => 14.99,
            'net' => 12.49,
            'vat' => 2.50,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PAID,
        ]);

        $handler = app(StripeCheckoutCompletionHandler::class);
        $payload = [
            'id' => 'cs_test_999',
            'payment_status' => 'paid',
            'metadata' => [
                'kind' => 'credit_pack',
                'payment_id' => (string) $payment->id,
                'report_type' => 'rebuild',
                'credits' => '1',
            ],
        ];

        $handler->handle($payload);
        $handler->handle($payload);

        $ledger = app(CreditLedgerService::class);
        $this->assertSame(0, $ledger->balance($user, 'rebuild'));
    }
}
