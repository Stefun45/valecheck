<?php

namespace Tests\Feature;

use App\Livewire\RegistrationQuickLook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationQuickLookTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_lookup_does_not_run_until_check_vehicle_is_clicked(): void
    {
        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB12CDE')
            ->assertSet('status', 'idle')
            ->assertSet('preview', null);
    }

    public function test_clicking_check_vehicle_shows_a_preview_and_asks_for_confirmation(): void
    {
        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB12CDE')
            ->call('check')
            ->assertSet('status', 'found')
            ->assertSee('Is this your vehicle?')
            ->assertSee('Simulated Data');
    }

    public function test_a_short_partial_input_is_rejected_as_invalid(): void
    {
        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB1')
            ->call('check')
            ->assertSet('status', 'invalid')
            ->assertSet('preview', null);
    }

    public function test_rejecting_the_preview_resets_it_for_another_attempt(): void
    {
        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB12CDE')
            ->call('check')
            ->call('reject')
            ->assertSet('status', 'idle')
            ->assertSet('preview', null);
    }

    public function test_the_free_preview_includes_full_mot_history_and_a_mileage_chart(): void
    {
        // The homepage widget must match the same-cost preview shown when
        // starting a check from inside the account (StartCheck) - it's
        // the same underlying provider call, so there's no reason for one
        // to show the full MOT history and the other not to.
        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB12CDE')
            ->set('status', 'found')
            ->set('preview', [
                'registration' => 'AB12CDE',
                'make' => 'FORD',
                'model' => 'FIESTA',
                'colour' => 'BLUE',
                'fuel_type' => 'PETROL',
                'year' => 2019,
                'engine_capacity' => null,
                'mot_status' => 'Valid',
                'tax_status' => 'Taxed',
                'tax_expiry_date' => '2027-05-01',
                'mot_history' => [
                    ['test_date' => '2023-06-01', 'result' => 'PASSED', 'mileage' => 20000, 'advisories' => []],
                    ['test_date' => '2024-06-01', 'result' => 'FAILED', 'mileage' => 28000, 'advisories' => ['Front tyre worn']],
                ],
            ])
            ->assertSeeText('Front tyre worn')
            ->assertSeeText('20,000')
            ->assertSeeText('28,000');
    }
}
