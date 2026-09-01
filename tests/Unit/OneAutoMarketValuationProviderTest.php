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

    private function vehicle(
        ?array $motHistory,
        ?string $writeOffCategory = null,
        array $damageLocations = [],
    ): VehicleData {
        return new VehicleData(
            registration: 'AB12CDE',
            vin: null, make: 'FORD', model: 'FIESTA', derivative: null, year: 2019,
            engine: null, fuel: null, transmission: null, colour: null, specification: null,
            writeOffCategory: $writeOffCategory, writeOffDate: $writeOffCategory ? '2024-01-01' : null,
            financeMarker: false, stolenMarker: false, highRiskMarker: false, scrappedMarker: false, imported: false, exported: false,
            previousKeepers: null, plateChanges: null, mileageAnomaly: false,
            motHistory: $motHistory ?? [], keeperHistory: [], confidence: 'high',
            damageLocations: $damageLocations,
        );
    }

    public function test_a_clean_vehicle_uses_uk_vehicle_data_and_maps_the_full_ladder(): void
    {
        // Confirmed against a real sandbox response.
        Http::fake([
            'api.oneautoapi.com/ukvehicledata/valuationfromvrm/v2*' => Http::response([
                'success' => true,
                'result' => [
                    'vehicle_data' => ['vehicle_registration_mark' => 'AB12CDE'],
                    'valuation_data' => [
                        'list_price_inc_delivery_vat' => 29903,
                        'dealer_forecourt' => 13550,
                        'trade_retail' => 12629,
                        'private_clean' => 11314,
                        'private_average' => 10884,
                        'part_exchange' => 10840,
                        'auction_value' => 10365,
                        'trade_average' => 9856,
                        'trade_poor' => 8384,
                    ],
                ],
            ], 200),
        ]);

        $vehicle = $this->vehicle([
            ['test_date' => '2023-06-01', 'mileage' => 20000],
            ['test_date' => '2024-06-01', 'mileage' => 28000],
        ]);

        $result = $this->provider()->getValuation($vehicle);

        $this->assertSame('ukvehicledata', $result->source);
        $this->assertSame(13550.0, $result->cleanValue);
        $this->assertSame(13550.0, $result->dealerForecourt);
        $this->assertSame(12629.0, $result->tradeValue);
        $this->assertSame(13550.0, $result->retailValue);
        $this->assertSame(11314.0, $result->privateValue);
        $this->assertSame(10884.0, $result->privateAverage);
        $this->assertSame(10840.0, $result->partExchange);
        $this->assertSame(10365.0, $result->auctionValue);
        $this->assertSame(9856.0, $result->tradeAverage);
        $this->assertSame(8384.0, $result->tradePoor);
        $this->assertSame(29903.0, $result->listPrice);
        $this->assertNull($result->categoryAdjustedLow);

        Http::assertSent(fn ($request) => $request['current_mileage'] === 28000);
    }

    public function test_a_written_off_vehicle_uses_salvageguide_instead_of_uk_vehicle_data(): void
    {
        // Response shape confirmed from One Auto's own documentation example.
        Http::fake([
            'api.oneautoapi.com/salvageguide/bidpredictionfromvrm*' => Http::response([
                'success' => true,
                'result' => [
                    'category_adjusted_retail_value_low_gbp' => 4595,
                    'category_adjusted_retail_value_high_gbp' => 5845,
                    'salvage_auction_predicted_bid_low_gbp' => 1989,
                    'salvage_auction_predicted_bid_average_gbp' => 2607,
                    'salvage_auction_predicted_bid_high_gbp' => 3438,
                ],
            ], 200),
        ]);

        $vehicle = $this->vehicle(
            [['test_date' => '2024-06-01', 'mileage' => 62069]],
            writeOffCategory: 'N',
            damageLocations: ['Front', 'FrontNearside'],
        );

        $result = $this->provider()->getValuation($vehicle);

        $this->assertSame('salvageguide', $result->source);
        $this->assertNull($result->cleanValue);
        $this->assertSame(4595.0, $result->categoryAdjustedLow);
        $this->assertSame(5845.0, $result->categoryAdjustedHigh);
        $this->assertSame(1989.0, $result->salvageAuctionBidLow);
        $this->assertSame(2607.0, $result->salvageAuctionBidAverage);
        $this->assertSame(3438.0, $result->salvageAuctionBidHigh);

        Http::assertSent(function ($request) {
            return $request['salvage_category'] === 'N'
                && $request['primary_damage_desc'] === 'Front'
                && $request['current_mileage'] === 62069;
        });
    }

    public function test_a_multi_word_damage_location_is_space_separated_for_the_request(): void
    {
        Http::fake([
            'api.oneautoapi.com/salvageguide/bidpredictionfromvrm*' => Http::response([
                'success' => true,
                'result' => ['category_adjusted_retail_value_low_gbp' => 1000, 'category_adjusted_retail_value_high_gbp' => 2000],
            ], 200),
        ]);

        $vehicle = $this->vehicle(
            [['test_date' => '2024-06-01', 'mileage' => 10000]],
            writeOffCategory: 'S',
            damageLocations: ['FrontNearside'],
        );

        $this->provider()->getValuation($vehicle);

        Http::assertSent(fn ($request) => $request['primary_damage_desc'] === 'Front Nearside');
    }

    public function test_no_mot_history_means_valuation_is_unavailable_not_fabricated(): void
    {
        $result = $this->provider()->getValuation($this->vehicle([]));

        $this->assertNull($result->cleanValue);
        $this->assertSame('unavailable', $result->confidence);
        Http::assertNothingSent();
    }

    public function test_a_clean_lookup_api_failure_degrades_to_unavailable_rather_than_throwing(): void
    {
        Http::fake([
            'api.oneautoapi.com/ukvehicledata/valuationfromvrm/v2*' => Http::response(['success' => false, 'error' => 'no data'], 200),
        ]);

        $result = $this->provider()->getValuation($this->vehicle([
            ['test_date' => '2024-06-01', 'mileage' => 28000],
        ]));

        $this->assertNull($result->cleanValue);
        $this->assertSame('unavailable', $result->confidence);
    }

    public function test_a_salvage_lookup_api_failure_degrades_to_unavailable_rather_than_throwing(): void
    {
        Http::fake([
            'api.oneautoapi.com/salvageguide/bidpredictionfromvrm*' => Http::response(['success' => false, 'error' => 'no data'], 200),
        ]);

        $result = $this->provider()->getValuation($this->vehicle(
            [['test_date' => '2024-06-01', 'mileage' => 28000]],
            writeOffCategory: 'N',
        ));

        $this->assertNull($result->categoryAdjustedLow);
        $this->assertSame('unavailable', $result->confidence);
    }
}
