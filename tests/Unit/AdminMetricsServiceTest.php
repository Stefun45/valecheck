<?php

namespace Tests\Unit;

use App\Models\ProviderEndpointCost;
use App\Models\ProviderLookupLog;
use App\Models\VehicleCheck;
use App\Services\Admin\AdminMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_spend_sums_the_cost_snapshotted_on_each_successful_log(): void
    {
        ProviderLookupLog::create(['provider' => 'oneauto', 'endpoint' => 'a', 'registration' => 'AB12CDE', 'status' => ProviderLookupLog::STATUS_SUCCESS, 'cost_net' => 0.05]);
        ProviderLookupLog::create(['provider' => 'oneauto', 'endpoint' => 'b', 'registration' => 'AB12CDE', 'status' => ProviderLookupLog::STATUS_SUCCESS, 'cost_net' => 0.12]);
        // A failed call's cost isn't counted as real spend.
        ProviderLookupLog::create(['provider' => 'oneauto', 'endpoint' => 'c', 'registration' => 'AB12CDE', 'status' => ProviderLookupLog::STATUS_FAILED, 'cost_net' => 0.99]);

        $metrics = app(AdminMetricsService::class)->compute();

        $this->assertEqualsWithDelta(0.17, $metrics['api_spend'], 0.0001);
    }

    public function test_editing_a_provider_cost_does_not_change_the_spend_already_reported_for_past_calls(): void
    {
        // Reproduces the exact scenario the site owner was worried about:
        // a call logged at the 5p rate, then the rate drops to 1p on a
        // better plan — past reporting must still show the original 5p.
        $endpointCost = ProviderEndpointCost::updateOrCreate(['endpoint' => 'oneauto/mothistoryandtaxstatus/v2'], ['cost_net' => 0.05]);
        ProviderLookupLog::create([
            'provider' => 'oneauto',
            'endpoint' => 'oneauto/mothistoryandtaxstatus/v2',
            'registration' => 'AB12CDE',
            'status' => ProviderLookupLog::STATUS_SUCCESS,
            'cost_net' => $endpointCost->cost_net,
        ]);

        $before = app(AdminMetricsService::class)->compute()['api_spend'];

        $endpointCost->update(['cost_net' => 0.01]);

        $after = app(AdminMetricsService::class)->compute()['api_spend'];

        $this->assertEqualsWithDelta(0.05, $before, 0.0001);
        $this->assertEqualsWithDelta($before, $after, 0.0001);
    }

    public function test_avg_cost_per_check_reflects_real_logged_calls_for_that_check_type_only(): void
    {
        $check = VehicleCheck::factory()->create(['type' => VehicleCheck::TYPE_CHECK, 'status' => VehicleCheck::STATUS_COMPLETED]);
        $plus = VehicleCheck::factory()->create(['type' => VehicleCheck::TYPE_PLUS, 'status' => VehicleCheck::STATUS_COMPLETED]);

        ProviderLookupLog::create(['provider' => 'oneauto', 'endpoint' => 'a', 'registration' => 'AB12CDE', 'vehicle_check_id' => $check->id, 'status' => ProviderLookupLog::STATUS_SUCCESS, 'cost_net' => 0.10]);
        ProviderLookupLog::create(['provider' => 'oneauto', 'endpoint' => 'b', 'registration' => 'CD34EFG', 'vehicle_check_id' => $plus->id, 'status' => ProviderLookupLog::STATUS_SUCCESS, 'cost_net' => 5.00]);

        $metrics = app(AdminMetricsService::class)->compute();

        $this->assertEqualsWithDelta(0.10, $metrics['avg_cost_per_check'], 0.0001);
    }
}
