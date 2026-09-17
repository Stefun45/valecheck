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

class SampleReportPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedSample(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['registration' => 'SAMP01A', 'make' => 'FORD', 'model' => 'FIESTA']);
        $check = VehicleCheck::factory()->create([
            'user_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => 'SAMP01A',
            'payment_id' => Payment::create([
                'user_id' => $owner->id, 'type' => 'plus', 'description' => 'ValeCheck Plus',
                'gross' => 11.99, 'net' => 9.99, 'vat' => 2.00, 'vat_rate' => 0.20,
                'currency' => 'GBP', 'status' => Payment::STATUS_PAID,
            ])->id,
        ]);
        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false]);
        VehicleValuation::create(['vehicle_check_id' => $check->id, 'source' => 'ukvehicledata', 'confidence' => 'high', 'dealer_forecourt' => 9000]);

        $this->artisan('demo:seed-sample-report', ['registration' => 'SAMP01A']);
    }

    public function test_a_guest_can_view_the_sample_report_with_no_login_required(): void
    {
        $this->seedSample();

        $this->get(route('sample-report'))
            ->assertOk()
            ->assertSeeText('This is a sample report')
            ->assertSeeText('Check Your Own Vehicle');
    }

    public function test_the_sample_report_is_indexable_with_its_own_title(): void
    {
        $this->seedSample();

        $this->get(route('sample-report'))
            ->assertOk()
            ->assertSee('<title>Sample Vehicle History Report — ValeCheck</title>', false)
            ->assertDontSee('name="robots" content="noindex', false);
    }

    public function test_it_404s_when_no_sample_has_been_seeded_yet(): void
    {
        $this->get(route('sample-report'))->assertNotFound();
    }

    public function test_a_vehicle_with_a_vin_does_not_crash_the_page_and_hides_the_dead_verify_form(): void
    {
        // Reproduces a real production crash: the sample page renders
        // plus-report.blade.php directly (no ShowCheck Livewire
        // component behind it), so when the source vehicle has a VIN
        // and the vin-verification partial tries to render, its
        // $vinToVerify/$vinMatchResult (normally Livewire public
        // properties) were undefined. The form also can't actually do
        // anything without that component, so it should be hidden here
        // entirely rather than merely not crash.
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['registration' => 'SAMP02A', 'vin' => 'WVWZZZ1JZXW000001']);
        VehicleCheck::factory()->create([
            'user_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => 'SAMP02A',
            'payment_id' => Payment::create([
                'user_id' => $owner->id, 'type' => 'plus', 'description' => 'ValeCheck Plus',
                'gross' => 11.99, 'net' => 9.99, 'vat' => 2.00, 'vat_rate' => 0.20,
                'currency' => 'GBP', 'status' => Payment::STATUS_PAID,
            ])->id,
        ]);
        $this->artisan('demo:seed-sample-report', ['registration' => 'SAMP02A']);

        $response = $this->get(route('sample-report'))->assertOk();

        $response->assertDontSeeText('Confirm the VIN on the V5C or dashboard matches');
        $response->assertDontSeeHtml('wire:submit="verifyVin"');
    }

    public function test_the_real_registration_is_masked_and_never_shown_on_the_public_page(): void
    {
        $this->seedSample();

        $response = $this->get(route('sample-report'))->assertOk();

        $response->assertDontSeeText('SAMP01A');
        $response->assertSeeText('AB12 SAM');

        // Masking only ever happens in memory for display — the stored
        // record still carries whatever real plate it was copied from.
        $this->assertSame('SAMP01A', VehicleCheck::where('is_sample', true)->firstOrFail()->registration);
    }
}
