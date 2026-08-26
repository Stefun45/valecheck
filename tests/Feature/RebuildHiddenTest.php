<?php

namespace Tests\Feature;

use App\Livewire\VehicleCheck\StartCheck;
use App\Models\User;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RebuildHiddenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['valecheck.rebuild_enabled' => false]);
    }

    public function test_the_landing_page_shows_only_two_plans(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeText('ValeCheck Plus')
            ->assertDontSeeText('ValeCheck Rebuild')
            ->assertDontSee('£14.99');
    }

    public function test_the_choose_step_shows_only_two_plans(): void
    {
        $this->get(route('vehicle-checks.start', ['registration' => 'AB12CDE']))
            ->assertOk()
            ->assertSeeText('ValeCheck Plus')
            ->assertDontSeeText('ValeCheck Rebuild')
            ->assertDontSee('Rebuild It');
    }

    public function test_choosing_rebuild_does_not_advance_past_the_choose_step(): void
    {
        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->assertSet('step', 'choose');
    }

    public function test_submit_refuses_to_create_a_rebuild_check_even_if_type_is_forced_directly(): void
    {
        $user = User::factory()->create();
        event(new Verified($user));
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->set('type', VehicleCheck::TYPE_REBUILD)
            ->set('mileage', 20000)
            ->call('submit')
            ->assertSet('step', 'choose');

        $this->assertDatabaseMissing('vehicle_checks', ['registration' => 'AB12CDE']);
    }

    public function test_billing_endpoints_are_unreachable(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('billing.credit-pack'), ['pack' => 'rebuild_1'])->assertNotFound();
        $this->post(route('billing.subscribe'), ['plan' => 'trader'])->assertNotFound();
    }

    public function test_verifying_a_new_user_grants_no_free_rebuild_credits(): void
    {
        $user = User::factory()->create();
        event(new Verified($user));

        $balance = app(CreditLedgerService::class)->balance($user, VehicleCheck::TYPE_REBUILD);

        $this->assertSame(0, $balance);
    }

    public function test_the_dashboard_hides_credit_and_subscription_sections(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Buy Rebuild credits')
            ->assertDontSeeText('Subscribe for regular checks');
    }
}
