<?php

namespace Tests\Unit;

use App\Models\ProviderLookupLog;
use App\Models\VehicleCheck;
use App\Services\OneAuto\OneAutoClient;
use App\Services\VehicleTax\OneAutoVehicleTaxCostProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneAutoVehicleTaxCostProviderTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): OneAutoVehicleTaxCostProvider
    {
        return new OneAutoVehicleTaxCostProvider(new OneAutoClient('test-key', 'https://api.oneautoapi.com'));
    }

    public function test_a_standard_rate_response_derives_the_six_month_rate_as_fifty_five_percent(): void
    {
        // Confirmed against a real sandbox response — the API never
        // returns a six-month figure at all, only annual rate(s).
        Http::fake([
            'api.oneautoapi.com/oneauto/vehicletaxfromvrm/v2*' => Http::response([
                'success' => true,
                'result' => [
                    'registration_month' => '2017-03',
                    'co2_gkm' => 139,
                    'type_approval_category' => 'M1',
                    'vehicle_type' => 'car',
                    'engine_capacity_cc' => 1999,
                    'dvla_revenue_weight_kg' => 2660,
                    'fuel_type_desc' => 'Diesel',
                    '12_month_rfl_y1' => null,
                    '12_month_rfl' => 195,
                    '12_month_rfl_premium' => null,
                    'is_premium' => null,
                ],
            ], 200),
        ]);

        $result = $this->provider()->check('DY17BXW');

        $this->assertTrue($result->available);
        $this->assertSame(195.0, $result->annualRate);
        $this->assertSame(107.25, $result->sixMonthRate);
        // type_approval_category ('M1') is not a real VED tax band —
        // deliberately not surfaced as one.
        $this->assertNull($result->taxClass);

        Http::assertSent(fn ($request) => $request['list_price_inc_options_delivery_vat'] === 0);
    }

    public function test_the_premium_rate_takes_priority_when_present(): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/vehicletaxfromvrm/v2*' => Http::response([
                'success' => true,
                'result' => [
                    '12_month_rfl_y1' => null,
                    '12_month_rfl' => 195,
                    '12_month_rfl_premium' => 620,
                    'is_premium' => true,
                ],
            ], 200),
        ]);

        $result = $this->provider()->check('AB12CDE');

        $this->assertSame(620.0, $result->annualRate);
        $this->assertSame(round(620 * 0.55, 2), $result->sixMonthRate);
    }

    public function test_a_first_year_rate_is_used_when_present_and_no_premium_applies(): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/vehicletaxfromvrm/v2*' => Http::response([
                'success' => true,
                'result' => [
                    '12_month_rfl_y1' => 540,
                    '12_month_rfl' => 195,
                    '12_month_rfl_premium' => null,
                    'is_premium' => null,
                ],
            ], 200),
        ]);

        $result = $this->provider()->check('AB12CDE');

        $this->assertSame(540.0, $result->annualRate);
    }

    public function test_a_response_with_no_rate_fields_at_all_is_unavailable(): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/vehicletaxfromvrm/v2*' => Http::response([
                'success' => true,
                'result' => ['vehicle_type' => 'car'],
            ], 200),
        ]);

        $result = $this->provider()->check('AB12CDE');

        $this->assertFalse($result->available);
        $this->assertNull($result->annualRate);
    }

    public function test_the_service_not_being_enabled_degrades_to_unavailable_rather_than_failing_the_report(): void
    {
        Http::fake([
            'api.oneautoapi.com/oneauto/vehicletaxfromvrm/v2*' => Http::response([
                'success' => false,
                'result' => ['error' => 'The requested service has not been enabled.'],
            ], 403),
        ]);

        $result = $this->provider()->check('AB12CDE');

        $this->assertFalse($result->available);
    }

    public function test_a_provider_timeout_degrades_to_unavailable_and_is_logged(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $result = $this->provider()->check('AB12CDE');

        $this->assertFalse($result->available);
        $this->assertSame(1, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_FAILED)->count());
    }

    public function test_every_call_is_logged_with_the_vehicle_check_id(): void
    {
        $check = VehicleCheck::factory()->create();

        Http::fake([
            'api.oneautoapi.com/oneauto/vehicletaxfromvrm/v2*' => Http::response([
                'success' => true,
                'result' => ['12_month_rfl' => 195],
            ], 200),
        ]);

        $this->provider()->check('AB12CDE', $check->id);

        $this->assertSame(1, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_SUCCESS)->count());
        $this->assertSame($check->id, ProviderLookupLog::first()->vehicle_check_id);
        $this->assertSame('oneauto/vehicletaxfromvrm/v2', ProviderLookupLog::first()->endpoint);
    }
}
