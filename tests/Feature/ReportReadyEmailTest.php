<?php

namespace Tests\Feature;

use App\Mail\ReportReadyEmail;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportReadyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_report_ready_email_with_a_pdf_attached_is_sent_on_completion(): void
    {
        Storage::fake('local');
        Mail::fake();

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
            'id' => 'cs_test_report_email',
            'payment_intent' => 'pi_test_report_email',
            'payment_status' => 'paid',
            'metadata' => [
                'kind' => 'vehicle_check',
                'vehicle_check_id' => (string) $check->id,
                'payment_id' => (string) $payment->id,
            ],
        ]);

        $check->refresh();
        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);

        // The PDF is generated and stored the moment the report completes —
        // not merely as a side effect of the email attachment being built.
        $this->assertTrue($check->report->hasPdf());

        Mail::assertSent(ReportReadyEmail::class, function (ReportReadyEmail $mail) use ($user, $check) {
            return $mail->hasTo($user->email)
                && $mail->vehicleCheck->is($check)
                && count($mail->attachments()) === 1;
        });
    }
}
