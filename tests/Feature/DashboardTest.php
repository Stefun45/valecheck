<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_the_plus_credit_balance(): void
    {
        $user = User::factory()->create();
        app(CreditLedgerService::class)->grantPurchasedCredits($user, VehicleCheck::TYPE_PLUS, 3);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Plus balance')
            ->assertSeeText('3');
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
