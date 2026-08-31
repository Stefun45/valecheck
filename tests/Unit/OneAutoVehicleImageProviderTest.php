<?php

namespace Tests\Unit;

use App\Models\ProviderLookupLog;
use App\Models\VehicleCheck;
use App\Services\OneAuto\OneAutoClient;
use App\Services\VehicleImagery\OneAutoVehicleImageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OneAutoVehicleImageProviderTest extends TestCase
{
    use RefreshDatabase;

    private function provider(): OneAutoVehicleImageProvider
    {
        return new OneAutoVehicleImageProvider(new OneAutoClient('test-key', 'https://api.oneautoapi.com'));
    }

    private function fakeBinaryPng(): string
    {
        return "\x89PNG\r\n\x1a\n".'fake-image-bytes';
    }

    public function test_a_matching_colour_is_requested_and_the_image_is_downloaded(): void
    {
        Http::fake([
            'api.oneautoapi.com/vehicleimagery/imagesearchfromvrm*' => Http::response([
                'success' => true,
                'result' => [
                    'images' => [
                        [
                            'image_ids' => ['front_right' => 'image-id-123'],
                            'colour_desc_list' => ['Gray', 'Blue', 'Red'],
                        ],
                    ],
                ],
            ], 200),
            'api.oneautoapi.com/vehicleimagery/imagefromid*' => Http::response([
                'success' => true,
                'result' => ['image_url' => 'https://cdn.example.com/vehicle.png'],
            ], 200),
            'cdn.example.com/*' => Http::response($this->fakeBinaryPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $result = $this->provider()->fetch('AB12CDE', 'Blue');

        $this->assertTrue($result->available);
        $this->assertSame($this->fakeBinaryPng(), $result->contents);
        $this->assertSame('image/png', $result->mimeType);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'imagefromid')
            && $request['generic_colour_desc'] === 'Blue'
            && $request['image_id'] === 'image-id-123'
            && $request['image_background'] === 'Transparent');
    }

    public function test_an_unavailable_colour_falls_back_to_the_first_available_one(): void
    {
        Http::fake([
            'api.oneautoapi.com/vehicleimagery/imagesearchfromvrm*' => Http::response([
                'success' => true,
                'result' => [
                    'images' => [
                        [
                            'image_ids' => ['front_right' => 'image-id-123'],
                            'colour_desc_list' => ['Gray', 'Blue'],
                        ],
                    ],
                ],
            ], 200),
            'api.oneautoapi.com/vehicleimagery/imagefromid*' => Http::response([
                'success' => true,
                'result' => ['image_url' => 'https://cdn.example.com/vehicle.png'],
            ], 200),
            'cdn.example.com/*' => Http::response($this->fakeBinaryPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->provider()->fetch('AB12CDE', 'Chartreuse');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'imagefromid')
            && $request['generic_colour_desc'] === 'Gray');
    }

    public function test_no_image_ids_returned_degrades_to_unavailable(): void
    {
        Http::fake([
            'api.oneautoapi.com/vehicleimagery/imagesearchfromvrm*' => Http::response([
                'success' => true,
                'result' => ['images' => []],
            ], 200),
        ]);

        $result = $this->provider()->fetch('AB12CDE', 'Blue');

        $this->assertFalse($result->available);
        $this->assertNull($result->contents);
    }

    public function test_a_search_failure_degrades_to_unavailable_rather_than_failing_the_report(): void
    {
        Http::fake([
            'api.oneautoapi.com/vehicleimagery/imagesearchfromvrm*' => Http::response(['success' => false, 'error' => 'service not enabled'], 403),
        ]);

        $result = $this->provider()->fetch('AB12CDE', 'Blue');

        $this->assertFalse($result->available);
    }

    public function test_a_resolve_failure_degrades_to_unavailable(): void
    {
        Http::fake([
            'api.oneautoapi.com/vehicleimagery/imagesearchfromvrm*' => Http::response([
                'success' => true,
                'result' => [
                    'images' => [
                        ['image_ids' => ['front_right' => 'image-id-123'], 'colour_desc_list' => ['Blue']],
                    ],
                ],
            ], 200),
            'api.oneautoapi.com/vehicleimagery/imagefromid*' => Http::response(['success' => false, 'error' => 'no content'], 204),
        ]);

        $result = $this->provider()->fetch('AB12CDE', 'Blue');

        $this->assertFalse($result->available);
    }

    public function test_a_download_failure_degrades_to_unavailable(): void
    {
        Http::fake([
            'api.oneautoapi.com/vehicleimagery/imagesearchfromvrm*' => Http::response([
                'success' => true,
                'result' => [
                    'images' => [
                        ['image_ids' => ['front_right' => 'image-id-123'], 'colour_desc_list' => ['Blue']],
                    ],
                ],
            ], 200),
            'api.oneautoapi.com/vehicleimagery/imagefromid*' => Http::response([
                'success' => true,
                'result' => ['image_url' => 'https://cdn.example.com/vehicle.png'],
            ], 200),
            'cdn.example.com/*' => Http::response('not found', 404),
        ]);

        $result = $this->provider()->fetch('AB12CDE', 'Blue');

        $this->assertFalse($result->available);
    }

    public function test_a_provider_timeout_degrades_to_unavailable_and_is_logged(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $result = $this->provider()->fetch('AB12CDE', 'Blue');

        $this->assertFalse($result->available);
        $this->assertSame(1, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_FAILED)->count());
    }

    public function test_both_api_calls_are_logged_with_the_vehicle_check_id(): void
    {
        $check = VehicleCheck::factory()->create();

        Http::fake([
            'api.oneautoapi.com/vehicleimagery/imagesearchfromvrm*' => Http::response([
                'success' => true,
                'result' => [
                    'images' => [
                        ['image_ids' => ['front_right' => 'image-id-123'], 'colour_desc_list' => ['Blue']],
                    ],
                ],
            ], 200),
            'api.oneautoapi.com/vehicleimagery/imagefromid*' => Http::response([
                'success' => true,
                'result' => ['image_url' => 'https://cdn.example.com/vehicle.png'],
            ], 200),
            'cdn.example.com/*' => Http::response($this->fakeBinaryPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->provider()->fetch('AB12CDE', 'Blue', $check->id);

        $this->assertSame(2, ProviderLookupLog::where('status', ProviderLookupLog::STATUS_SUCCESS)->where('vehicle_check_id', $check->id)->count());
        $this->assertSame(
            ['vehicleimagery/imagesearchfromvrm', 'vehicleimagery/imagefromid'],
            ProviderLookupLog::orderBy('id')->pluck('endpoint')->all()
        );
    }

    public function test_the_binary_image_download_itself_is_not_logged_as_a_provider_lookup(): void
    {
        Http::fake([
            'api.oneautoapi.com/vehicleimagery/imagesearchfromvrm*' => Http::response([
                'success' => true,
                'result' => [
                    'images' => [
                        ['image_ids' => ['front_right' => 'image-id-123'], 'colour_desc_list' => ['Blue']],
                    ],
                ],
            ], 200),
            'api.oneautoapi.com/vehicleimagery/imagefromid*' => Http::response([
                'success' => true,
                'result' => ['image_url' => 'https://cdn.example.com/vehicle.png'],
            ], 200),
            'cdn.example.com/*' => Http::response($this->fakeBinaryPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->provider()->fetch('AB12CDE', 'Blue');

        // Exactly the two named One Auto endpoint calls — the CDN download
        // is a separate, non-billable-as-a-second-call operation.
        $this->assertSame(2, ProviderLookupLog::count());
    }
}
