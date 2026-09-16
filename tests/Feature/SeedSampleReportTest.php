<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use App\Models\VehicleValuation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedSampleReportTest extends TestCase
{
    use RefreshDatabase;

    private function completedPlusCheck(string $registration): VehicleCheck
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['registration' => $registration]);
        $check = VehicleCheck::factory()->create([
            'user_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => $registration,
            'payment_id' => Payment::create([
                'user_id' => $owner->id, 'type' => 'plus', 'description' => 'ValeCheck Plus',
                'gross' => 11.99, 'net' => 9.99, 'vat' => 2.00, 'vat_rate' => 0.20,
                'currency' => 'GBP', 'status' => Payment::STATUS_PAID,
            ])->id,
        ]);

        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false]);
        VehicleValuation::create(['vehicle_check_id' => $check->id, 'source' => 'ukvehicledata', 'confidence' => 'high', 'dealer_forecourt' => 9000]);

        return $check;
    }

    public function test_it_copies_a_completed_plus_check_onto_a_dedicated_sample_account(): void
    {
        $source = $this->completedPlusCheck('SAMP01A');

        $this->artisan('demo:seed-sample-report', ['registration' => 'SAMP01A'])->assertSuccessful();

        $sample = VehicleCheck::where('is_sample', true)->firstOrFail();

        $this->assertNotSame($source->id, $sample->id);
        $this->assertSame('SAMP01A', $sample->registration);
        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $sample->status);
        $this->assertNotSame($source->user_id, $sample->user_id);
        $this->assertNotNull($sample->history);
        $this->assertNotNull($sample->valuation);
        $this->assertNull($sample->payment_id);

        // The source and its real owner are completely untouched.
        $this->assertFalse($source->fresh()->is_sample);
    }

    public function test_re_running_the_command_replaces_rather_than_duplicates_the_sample(): void
    {
        $this->completedPlusCheck('SAMP01A');
        $this->artisan('demo:seed-sample-report', ['registration' => 'SAMP01A']);
        $firstSampleId = VehicleCheck::where('is_sample', true)->firstOrFail()->id;

        $this->completedPlusCheck('SAMP02B');
        $this->artisan('demo:seed-sample-report', ['registration' => 'SAMP02B'])->assertSuccessful();

        $this->assertSame(1, VehicleCheck::where('is_sample', true)->count());
        $this->assertSame('SAMP02B', VehicleCheck::where('is_sample', true)->firstOrFail()->registration);
        $this->assertDatabaseMissing('vehicle_checks', ['id' => $firstSampleId]);
    }

    public function test_it_fails_gracefully_for_a_registration_with_no_completed_plus_check(): void
    {
        $this->artisan('demo:seed-sample-report', ['registration' => 'NOPE123'])->assertFailed();

        $this->assertSame(0, VehicleCheck::where('is_sample', true)->count());
    }
}
