<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Report;
use App\Models\User;
use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use App\Models\VehicleValuation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupExperianDemoAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_account_and_copies_the_check_onto_it(): void
    {
        $owner = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $owner->id,
            'type' => Payment::TYPE_PLUS,
            'description' => 'ValeCheck Plus',
            'gross' => 11.99,
            'net' => 9.99,
            'vat' => 2.00,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PAID,
        ]);
        $check = VehicleCheck::factory()->create([
            'user_id' => $owner->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => 'DY17BXW',
            'payment_id' => $payment->id,
        ]);
        VehicleHistory::create(['vehicle_check_id' => $check->id, 'write_off_category' => 'N', 'finance_marker' => false]);
        VehicleValuation::create(['vehicle_check_id' => $check->id, 'salvage_adjusted_value' => 7750, 'confidence' => 'medium']);
        Report::create([
            'vehicle_check_id' => $check->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'headline_summary' => 'Test.',
            'pdf_disk' => 'local',
            'pdf_path' => 'reports/original.pdf',
        ]);

        $this->artisan('demo:setup-experian-account', [
            'registration' => 'DY17BXW',
            'email' => 'reviewer@experian.example',
            'password' => 'a-real-password',
        ])->assertSuccessful();

        $reviewer = User::where('email', 'reviewer@experian.example')->firstOrFail();
        $this->assertTrue(Hash::check('a-real-password', $reviewer->password));

        $copy = VehicleCheck::where('user_id', $reviewer->id)->where('registration', 'DY17BXW')->firstOrFail();
        $this->assertNotSame($check->id, $copy->id);
        $this->assertNotSame($check->public_id, $copy->public_id);
        $this->assertNull($copy->payment_id);

        // The original, and its real owner's copy, are completely untouched.
        $this->assertSame($owner->id, $check->fresh()->user_id);
        $this->assertSame($payment->id, $check->fresh()->payment_id);

        $this->assertSame('N', $copy->history->write_off_category);
        $this->assertSame('7750.00', $copy->valuation->salvage_adjusted_value);
        $this->assertNotNull($copy->report);
        $this->assertNull($copy->report->pdf_path, 'The PDF should regenerate fresh under the new check, not reuse the original file.');
    }

    public function test_running_it_again_resets_the_password_and_adds_a_second_copy(): void
    {
        $owner = User::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $owner->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => 'DY17BXW',
        ]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test.']);

        $this->artisan('demo:setup-experian-account', [
            'registration' => 'DY17BXW', 'email' => 'reviewer@experian.example', 'password' => 'first-password',
        ])->assertSuccessful();

        $this->artisan('demo:setup-experian-account', [
            'registration' => 'DY17BXW', 'email' => 'reviewer@experian.example', 'password' => 'second-password',
        ])->assertSuccessful();

        $this->assertSame(1, User::where('email', 'reviewer@experian.example')->count());
        $reviewer = User::where('email', 'reviewer@experian.example')->firstOrFail();
        $this->assertTrue(Hash::check('second-password', $reviewer->password));
        $this->assertSame(2, VehicleCheck::where('user_id', $reviewer->id)->count());
    }

    public function test_it_fails_gracefully_when_no_completed_check_exists_for_the_registration(): void
    {
        $this->artisan('demo:setup-experian-account', [
            'registration' => 'NOTFOUND1', 'email' => 'reviewer@experian.example', 'password' => 'a-real-password',
        ])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'reviewer@experian.example']);
    }
}
