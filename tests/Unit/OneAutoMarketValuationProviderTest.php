<?php

namespace Tests\Unit;

use App\DataTransferObjects\VehicleData;
use App\Services\OneAuto\OneAutoClient;
use App\Services\Valuation\OneAutoMarketValuationProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneAutoMarketValuationProviderTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): OneAutoMarketValuationProvider
    {
        return new OneAutoMarketValuationProvider(new OneAutoClient('test-key', 'https://api.oneautoapi.com'));
    }

    private function vehicleWithMileage(?array $motHistory): VehicleData
    {
        return new VehicleData(
            registration: 'AB12CDE',
            vin: null, make: 'FORD', model: 'FIESTA', derivative: null, year: 2019,
            engine: null, fuel: null, transmission: null, colour: null, specification: null,
            writeOffCategory: null, writeOffDate: null,
            financeMarker: false, stolenMarker: false, highRiskMarker: false, scrappedMarker: false, imported: false, exported: false,
            previousKeepers: null, plateChanges: null, mileageAnomaly: false,
            motHistory: $motHistory ?? [], keeperHistory: [], confidence: 'high',
        );
    }

    public function test_a_successful_valuation_uses_the_most_recent_mot_mileage(): void
    {
        Http::fake([
            'api.oneautoapi.com/brego/valuationfromvrm/v2*' => Http::response([
                'success' => true,
                'result' => [
                    'trade_low_valuation' => 8000, 'trade_average_valuation' => 8500, 'trade_high_valuation' => 9000,
                    'retail_low_valuation' => 9500, 'retail_average_valuation' => 10000, 'retail_high_valuation' => 10500,
                ],
            ], 200),
        ]);

        $vehicle = $this->vehicleWithMileage([
            ['test_date' => '2023-06-01', 'mileage' => 20000],
            ['test_date' => '2024-06-01', 'mileage' => 28000],
        ]);

        $result = $this->provider()->getValuation($vehicle);

        $this->assertSame(10000.0, $result->cleanValue);
        $this->assertSame(8500.0, $result->tradeValue);
        $this->assertSame(10500.0, $result->retailValue);
        $this->assertNull($result->privateValue);

        Http::assertSent(fn ($request) => $request['current_mileage'] === 28000);
    }

    public function test_no_mot_history_means_valuation_is_unavailable_not_fabricated(): void
    {
        $result = $this->provider()->getValuation($this->vehicleWithMileage([]));

        $this->assertNull($result->cleanValue);
        $this->assertSame('unavailable', $result->confidence);
        Http::assertNothingSent();
    }

    public function test_an_api_failure_degrades_to_unavailable_rather_than_throwing(): void
    {
        Http::fake([
            'api.oneautoapi.com/brego/valuationfromvrm/v2*' => Http::response(['success' => false, 'error' => 'no data'], 200),
        ]);

        $result = $this->provider()->getValuation($this->vehicleWithMileage([
            ['test_date' => '2024-06-01', 'mileage' => 28000],
        ]));

        $this->assertNull($result->cleanValue);
        $this->assertSame('unavailable', $result->confidence);
    }
}
