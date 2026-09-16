<?php

namespace Tests\Unit;

use App\Models\ProviderEndpointCost;
use App\Models\ProviderLookupLog;
use App\Services\OneAuto\OneAutoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneAutoClientTest extends TestCase
{
    use RefreshDatabase;

    private function client(): OneAutoClient
    {
        return new OneAutoClient('test-key', 'https://api.oneautoapi.com');
    }

    public function test_a_successful_call_snapshots_the_endpoints_current_cost_onto_the_log(): void
    {
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'experian/autocheck/v3'], ['cost_net' => 0.12]);

        Http::fake(['api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => true, 'result' => []], 200)]);

        $this->client()->get('experian/autocheck/v3', 'AB12CDE', []);

        $this->assertSame('0.1200', ProviderLookupLog::first()->cost_net);
    }

    public function test_changing_the_cost_later_does_not_rewrite_a_log_already_recorded(): void
    {
        $endpointCost = ProviderEndpointCost::updateOrCreate(['endpoint' => 'experian/autocheck/v3'], ['cost_net' => 0.12]);

        Http::fake(['api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => true, 'result' => []], 200)]);

        $this->client()->get('experian/autocheck/v3', 'AB12CDE', []);
        $loggedCost = ProviderLookupLog::first()->cost_net;

        $endpointCost->update(['cost_net' => 0.01]);

        $this->assertSame('0.1200', $loggedCost);
        $this->assertSame('0.1200', ProviderLookupLog::first()->fresh()->cost_net);
        $this->assertSame('0.0100', ProviderEndpointCost::first()->fresh()->cost_net);
    }

    public function test_a_failed_call_is_also_snapshotted_with_the_endpoints_cost(): void
    {
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'experian/autocheck/v3'], ['cost_net' => 0.12]);

        Http::fake(['api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => false, 'error' => 'no data'], 200)]);

        try {
            $this->client()->get('experian/autocheck/v3', 'AB12CDE', []);
        } catch (\Throwable) {
            // Expected — the log write is what's under test here.
        }

        $this->assertSame('0.1200', ProviderLookupLog::first()->cost_net);
    }

    public function test_an_endpoint_with_no_configured_cost_logs_a_null_cost_rather_than_guessing(): void
    {
        Http::fake(['api.oneautoapi.com/some/unconfigured/endpoint*' => Http::response(['success' => true, 'result' => []], 200)]);

        $this->client()->get('some/unconfigured/endpoint', 'AB12CDE', []);

        $this->assertNull(ProviderLookupLog::first()->cost_net);
    }
}
