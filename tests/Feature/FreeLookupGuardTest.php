<?php

namespace Tests\Feature;

use App\Livewire\RegistrationQuickLook;
use App\Livewire\VehicleCheck\StartCheck;
use App\Models\FreeLookupLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FreeLookupGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_start_check_pages_own_preview_is_rate_limited_too(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Livewire::test(StartCheck::class)
                ->set('registration', 'AB12CDE')
                ->call('lookupVehicle')
                ->assertSet('previewStatus', 'found');
        }

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->assertSet('previewStatus', 'rate_limited')
            // Rate limited still lets them continue to the real, paid check.
            ->assertSet('vehicleConfirmed', true);
    }

    public function test_the_homepage_widget_and_the_start_check_page_share_one_rate_limit_budget(): void
    {
        // Using both entry points must not double the real limit — six
        // lookups on one and five on the other should exhaust the same
        // 10-per-hour budget from one IP.
        for ($i = 0; $i < 6; $i++) {
            Livewire::test(RegistrationQuickLook::class)
                ->set('registration', 'AB12CDE')
                ->call('check')
                ->assertSet('status', 'found');
        }

        for ($i = 0; $i < 4; $i++) {
            Livewire::test(StartCheck::class)
                ->set('registration', 'AB12CDE')
                ->call('lookupVehicle')
                ->assertSet('previewStatus', 'found');
        }

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->assertSet('previewStatus', 'rate_limited');

        Livewire::test(RegistrationQuickLook::class)
            ->set('registration', 'AB12CDE')
            ->call('check')
            ->assertSet('status', 'rate_limited');
    }

    public function test_every_genuine_attempt_from_either_entry_point_is_logged(): void
    {
        Livewire::test(RegistrationQuickLook::class)->set('registration', 'AB12CDE')->call('check');
        Livewire::test(StartCheck::class)->set('registration', 'CD34EFG')->call('lookupVehicle');

        $this->assertSame(2, FreeLookupLog::count());
        $this->assertSame(1, FreeLookupLog::where('source', FreeLookupLog::SOURCE_HOMEPAGE)->count());
        $this->assertSame(1, FreeLookupLog::where('source', FreeLookupLog::SOURCE_START_CHECK)->count());
    }

    public function test_an_invalid_format_attempt_is_not_logged(): void
    {
        Livewire::test(RegistrationQuickLook::class)->set('registration', 'AB1')->call('check');

        $this->assertSame(0, FreeLookupLog::count());
    }
}
