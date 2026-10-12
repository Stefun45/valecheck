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

    /**
     * Most endpoints are billed flat regardless of outcome, which the tests
     * above cover. A handful of endpoints (see
     * OneAutoSalvageAuctionImageProvider) are billed conditionally on HTTP
     * status instead — this is the generic mechanism that makes that
     * possible without touching any of the ~20 other call sites.
     */
    public function test_a_cost_resolver_overrides_the_flat_lookup_when_provided(): void
    {
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'experian/autocheck/v3'], ['cost_net' => 0.12]);

        Http::fake(['api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => true, 'result' => []], 200)]);

        $this->client()->get('experian/autocheck/v3', 'AB12CDE', [], null, fn (?int $httpStatus) => 1.2345);

        $this->assertSame('1.2345', ProviderLookupLog::first()->cost_net);
    }

    public function test_a_cost_resolver_is_given_the_actual_http_status_of_the_response(): void
    {
        Http::fake(['api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => true, 'result' => []], 206)]);

        $seenStatus = null;
        $this->client()->get('experian/autocheck/v3', 'AB12CDE', [], null, function (?int $httpStatus) use (&$seenStatus) {
            $seenStatus = $httpStatus;

            return 0.0;
        });

        $this->assertSame(206, $seenStatus);
        $this->assertSame('0.0000', ProviderLookupLog::first()->cost_net);
    }

    public function test_omitting_the_cost_resolver_keeps_the_existing_flat_lookup_behaviour(): void
    {
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'experian/autocheck/v3'], ['cost_net' => 0.12]);

        Http::fake(['api.oneautoapi.com/experian/autocheck/v3*' => Http::response(['success' => true, 'result' => []], 200)]);

        // No 5th argument — every one of the ~20 existing call sites calls
        // get() exactly like this, and must keep behaving identically.
        $this->client()->get('experian/autocheck/v3', 'AB12CDE', []);

        $this->assertSame('0.1200', ProviderLookupLog::first()->cost_net);
    }
}
