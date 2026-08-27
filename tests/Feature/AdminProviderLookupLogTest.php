<?php

namespace Tests\Feature;

use App\Models\ProviderLookupLog;
use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProviderLookupLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_view_provider_lookups(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.provider-lookups.index'))
            ->assertForbidden();
    }

    public function test_admin_can_see_lookup_history_without_any_secrets_exposed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $check = VehicleCheck::factory()->create(['registration' => 'AB12CDE']);

        ProviderLookupLog::create([
            'provider' => 'oneauto',
            'endpoint' => 'experian/autocheck/v3',
            'registration' => 'AB12CDE',
            'vehicle_check_id' => $check->id,
            'status' => ProviderLookupLog::STATUS_SUCCESS,
            'http_status' => 200,
        ]);

        ProviderLookupLog::create([
            'provider' => 'oneauto',
            'endpoint' => 'brego/valuationfromvrm/v2',
            'registration' => 'CD34EFG',
            'status' => ProviderLookupLog::STATUS_FAILED,
            'http_status' => 500,
            'error_message' => 'Timed out',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.provider-lookups.index'))
            ->assertOk()
            ->assertSeeText('experian/autocheck/v3')
            ->assertSeeText('AB12CDE')
            ->assertSeeText('Success')
            ->assertSeeText('Failed');

        $response->assertDontSee('ONEAUTO_API_KEY');
        $response->assertDontSee('x-api-key');
    }
}
