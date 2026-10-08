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

    public function test_repeated_checks_from_the_same_ip_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Livewire::test(RegistrationQuickLook::class)
                ->set('registration', 'AB12CDE')
                ->call('check')
                ->assertSet('status', 'found');
        }

        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB12CDE')
            ->call('check')
            ->assertSet('status', 'rate_limited')
            ->assertSet('preview', null);
    }

    public function test_an_invalid_format_attempt_does_not_count_towards_the_rate_limit(): void
    {
        for ($i = 0; $i < 20; $i++) {
            Livewire::test(RegistrationQuickLook::class)
                ->set('registration', 'AB1')
                ->call('check')
                ->assertSet('status', 'invalid');
        }

        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB12CDE')
            ->call('check')
            ->assertSet('status', 'found');
    }

    public function test_the_free_preview_shows_a_mot_and_mileage_teaser_not_the_full_history(): void
    {
        // Giving away the full MOT history and mileage chart before
        // checkout left nothing for a purchase to unlock - the homepage
        // widget must match the same teaser shown when starting a check
        // from inside the account (StartCheck): a real pass/fail summary
        // and trend line, never the full per-test breakdown or advisories.
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
            ->assertSeeText('2 MOTs on record')
            ->assertSeeText('1 passed, 1 failed')
            ->assertSeeText('Mileage trend: increasing consistently across 2 MOTs')
            ->assertDontSeeText('Front tyre worn')
            ->assertDontSeeText('20,000')
            ->assertDontSeeText('28,000');
    }

    public function test_the_confirm_button_signals_that_the_next_step_is_choosing_a_report(): void
    {
        // Production data showed ~110 free lookups and almost no
        // purchases - part of the problem was a confirm button that gave
        // no hint that clicking led towards pricing/a purchase decision.
        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB12CDE')
            ->call('check')
            ->assertSeeText("Yes, that's it", false)
            ->assertSeeText('see report options');
    }
}
