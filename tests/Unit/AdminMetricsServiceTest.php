<?php

namespace Tests\Unit;

use App\Models\ProductPrice;
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

    public function test_max_cost_per_check_sums_only_the_two_endpoints_a_check_can_ever_call(): void
    {
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'experian/autocheck/v3'], ['cost_net' => 0.10]);
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'oneauto/mothistoryandtaxstatus/v2'], ['cost_net' => 0.05]);
        // Plus-only endpoints must not leak into the Check figure.
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'carguide/salvagecheck/v2'], ['cost_net' => 99]);

        $metrics = app(AdminMetricsService::class)->compute();

        $this->assertEqualsWithDelta(0.15, $metrics['max_cost_per_check'], 0.0001);
    }

    public function test_max_cost_per_plus_takes_whichever_valuation_endpoint_currently_costs_more(): void
    {
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'ukvehicledata/valuationfromvrm/v2'], ['cost_net' => 0.20]);
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'salvageguide/bidpredictionfromvrm'], ['cost_net' => 0.50]);

        $metrics = app(AdminMetricsService::class)->compute();

        // Only one of the two valuation endpoints is ever actually called
        // per report, so the max-cost ceiling must use the pricier one,
        // not both added together.
        $this->assertEqualsWithDelta(0.50, $metrics['max_cost_per_plus'], 0.0001);
    }

    public function test_max_cost_updates_immediately_when_a_provider_cost_changes_unlike_avg_cost(): void
    {
        // This is the deliberate opposite of the historical snapshot
        // behaviour tested above — "max cost" is a live, forward-looking
        // figure, so it must reflect an edit the moment it's saved.
        $endpointCost = ProviderEndpointCost::updateOrCreate(['endpoint' => 'experian/autocheck/v3'], ['cost_net' => 0.10]);

        $before = app(AdminMetricsService::class)->compute()['max_cost_per_check'];

        $endpointCost->update(['cost_net' => 0.01]);

        $after = app(AdminMetricsService::class)->compute()['max_cost_per_check'];

        $this->assertGreaterThan($after, $before);
    }

    public function test_max_margin_per_check_is_the_selling_price_minus_the_worst_case_cost(): void
    {
        ProductPrice::updateOrCreate(['type' => 'check'], ['gross' => 8.99]);
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'experian/autocheck/v3'], ['cost_net' => 1.00]);
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'oneauto/mothistoryandtaxstatus/v2'], ['cost_net' => 0.50]);

        $metrics = app(AdminMetricsService::class)->compute();

        $this->assertEqualsWithDelta(7.49, $metrics['max_margin_per_check'], 0.0001);
    }
}
