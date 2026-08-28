<?php

namespace Tests\Unit;

use App\Models\ProviderLookupLog;
use App\Models\VehicleCheck;
use App\Services\OneAuto\OneAutoClient;
use App\Services\SalvageAuction\OneAutoSalvageAuctionProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneAutoSalvageAuctionProviderTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): OneAutoSalvageAuctionProvider
    {
        return new OneAutoSalvageAuctionProvider(new OneAutoClient('test-key', 'https://api.oneautoapi.com'));
    }

    public function test_a_found_record_maps_every_field_correctly(): void
    {
        Http::fake([
            'api.oneautoapi.com/carguide/salvagecheck/v2*' => Http::response([
                'success' => true,
                'result' => [
                    'salvage_auction_record_found' => true,
                    'salvage_auction_records' => [
                        [
                            'salvage_auction_lot_desc' => 'Category N — front end collision damage',
                            'salvage_auction_lot_date' => '2024-03-01',
                            'mileage' => 32000,
                            'primary_damage_desc' => 'Front bumper and headlight',
                            'secondary_damage_desc' => 'Nearside front wing',
                            'salvage_auction_location' => 'Copart Bedford',
                            'external_image_urls' => ['https://example.com/photo1.jpg', 'https://example.com/photo2.jpg'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->provider()->check('AB12CDE');

        $this->assertTrue($result->recordFound);
        $this->assertCount(1, $result->records);
        $this->assertSame('Category N — front end collision damage', $result->records[0]['lotDescription']);
        $this->assertSame('2024-03-01', $result->records[0]['lotDate']);
        $this->assertSame(32000, $result->records[0]['mileage']);
        $this->assertSame('Front bumper and headlight', $result->records[0]['primaryDamageDescription']);
        $this->assertSame('Nearside front wing', $result->records[0]['secondaryDamageDescription']);
        $this->assertSame('Copart Bedford', $result->records[0]['location']);
        $this->assertSame(['https://example.com/photo1.jpg', 'https://example.com/photo2.jpg'], $result->records[0]['imageUrls']);
    }

    public function test_no_record_found_returns_an_empty_not_found_result(): void
    {
        Http::fake([
            'api.oneautoapi.com/carguide/salvagecheck/v2*' => Http::response([
                'success' => true,
                'result' => ['salvage_auction_record_found' => false],
            ], 200),
        ]);

        $result = $this->provider()->check('AB12CDE');

        $this->assertFalse($result->recordFound);
        $this->assertSame([], $result->records);
    }

    public function test_an_api_failure_degrades_to_not_found_rather_than_failing_the_report(): void
    {
        Http::fake([
            'api.oneautoapi.com/carguide/salvagecheck/v2*' => Http::response(['success' => false, 'error' => 'no data'], 200),
        ]);

        $result = $this->provider()->check('AB12CDE');

        $this->assertFalse($result->recordFound);
        $this->assertSame([], $result->records);
    }

    public function test_a_provider_timeout_degrades_to_not_found_and_is_logged(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $result = $this->provider()->check('AB12CDE');

        $this->assertFalse($result->recordFound);
        $this->assertSame(1, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_FAILED)->count());
    }

    public function test_every_call_is_logged_with_the_vehicle_check_id(): void
    {
        $check = VehicleCheck::factory()->create();

        Http::fake([
            'api.oneautoapi.com/carguide/salvagecheck/v2*' => Http::response([
                'success' => true,
                'result' => ['salvage_auction_record_found' => false],
            ], 200),
        ]);

        $this->provider()->check('AB12CDE', $check->id);

        $this->assertSame(1, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_SUCCESS)->count());
        $this->assertSame($check->id, ProviderLookupLog::first()->vehicle_check_id);
        $this->assertSame('carguide/salvagecheck/v2', ProviderLookupLog::first()->endpoint);
    }
}
