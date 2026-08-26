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
}
