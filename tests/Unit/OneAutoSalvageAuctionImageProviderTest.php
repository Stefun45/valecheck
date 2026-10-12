<?php

namespace Tests\Unit;

use App\Models\ProviderEndpointCost;
use App\Models\ProviderLookupLog;
use App\Models\VehicleCheck;
use App\Services\OneAuto\OneAutoClient;
use App\Services\SalvageAuction\OneAutoSalvageAuctionImageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OneAutoSalvageAuctionImageProviderTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): OneAutoSalvageAuctionImageProvider
    {
        return new OneAutoSalvageAuctionImageProvider(new OneAutoClient('test-key', 'https://api.oneautoapi.com'));
    }

    public function test_a_found_set_of_images_is_returned(): void
    {
        Http::fake([
            'api.oneautoapi.com/salvageguide/thirdpartyimages*' => Http::response([
                'success' => true,
                'result' => ['third_party_image_urls' => ['https://example.com/photo1.jpg', 'https://example.com/photo2.jpg']],
                'error' => null,
            ], 200),
        ]);

        $images = $this->provider()->fetchImages('1234567');

        $this->assertSame(['https://example.com/photo1.jpg', 'https://example.com/photo2.jpg'], $images);
    }

    public function test_an_api_failure_degrades_to_an_empty_array_rather_than_throwing(): void
    {
        Http::fake([
            'api.oneautoapi.com/salvageguide/thirdpartyimages*' => Http::response(['success' => false, 'error' => 'no data'], 200),
        ]);

        $images = $this->provider()->fetchImages('1234567');

        $this->assertSame([], $images);
    }

    public function test_a_provider_timeout_degrades_to_an_empty_array_and_is_logged(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $images = $this->provider()->fetchImages('1234567');

        $this->assertSame([], $images);
        $this->assertSame(1, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_FAILED)->count());
    }

    /**
     * The single most important correctness property of this whole
     * feature: unlike every other One Auto endpoint, billing here depends
     * on the HTTP status, not on whether the call was logged at all. A
     * genuine HTTP 200 is the only chargeable outcome.
     */
    public function test_a_http_200_response_logs_the_real_configured_cost(): void
    {
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'salvageguide/thirdpartyimages'], ['cost_net' => 0.75]);

        Http::fake([
            'api.oneautoapi.com/salvageguide/thirdpartyimages*' => Http::response([
                'success' => true,
                'result' => ['third_party_image_urls' => ['https://example.com/photo1.jpg']],
                'error' => null,
            ], 200),
        ]);

        $this->provider()->fetchImages('1234567');

        $this->assertSame('0.7500', ProviderLookupLog::first()->cost_net);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function nonChargeableStatuses(): iterable
    {
        yield 'accepted/processing' => [202];
        yield 'no content' => [204];
        yield 'partial content' => [206];
        yield 'bad request' => [400];
        yield 'forbidden' => [403];
        yield 'too many requests' => [429];
        yield 'server error' => [500];
        yield 'service unavailable' => [503];
    }

    #[DataProvider('nonChargeableStatuses')]
    public function test_a_non_200_response_logs_exactly_zero_cost_regardless_of_the_configured_rate(int $httpStatus): void
    {
        ProviderEndpointCost::updateOrCreate(['endpoint' => 'salvageguide/thirdpartyimages'], ['cost_net' => 0.75]);

        Http::fake([
            'api.oneautoapi.com/salvageguide/thirdpartyimages*' => Http::response(
                $httpStatus < 300 ? ['success' => true, 'result' => [], 'error' => null] : ['success' => false, 'error' => 'not chargeable'],
                $httpStatus,
            ),
        ]);

        $this->provider()->fetchImages('1234567');

        $this->assertSame('0.0000', ProviderLookupLog::first()->cost_net);
    }

    public function test_every_call_is_logged_with_the_vehicle_check_id(): void
    {
        $check = VehicleCheck::factory()->create();

        Http::fake([
            'api.oneautoapi.com/salvageguide/thirdpartyimages*' => Http::response([
                'success' => true,
                'result' => ['third_party_image_urls' => []],
                'error' => null,
            ], 200),
        ]);

        $this->provider()->fetchImages('1234567', $check->id);

        $this->assertSame(1, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_SUCCESS)->count());
        $this->assertSame($check->id, ProviderLookupLog::first()->vehicle_check_id);
        $this->assertSame('salvageguide/thirdpartyimages', ProviderLookupLog::first()->endpoint);
    }
}
