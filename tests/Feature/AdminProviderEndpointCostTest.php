<?php

namespace Tests\Feature;

use App\Models\ProviderEndpointCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProviderEndpointCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_access_provider_cost_admin_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.provider-costs.edit'))->assertForbidden();
    }

    public function test_an_admin_can_view_and_update_provider_costs(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.provider-costs.edit'))->assertOk();
        $response->assertSeeText('experian/autocheck/v3');

        $update = collect(ProviderEndpointCost::ENDPOINTS)
            ->keys()
            ->mapWithKeys(fn (string $endpoint) => [ProviderEndpointCost::fieldName($endpoint) => '0.05'])
            ->all();
        $update[ProviderEndpointCost::fieldName('oneauto/mothistoryandtaxstatus/v2')] = '0.01';

        $this->actingAs($admin)
            ->put(route('admin.provider-costs.update'), $update)
            ->assertRedirect(route('admin.provider-costs.edit'));

        $this->assertSame('0.0100', ProviderEndpointCost::where('endpoint', 'oneauto/mothistoryandtaxstatus/v2')->value('cost_net'));
    }
}
