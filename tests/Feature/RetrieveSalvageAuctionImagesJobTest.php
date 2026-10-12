<?php

namespace Tests\Feature;

use App\Jobs\RetrieveSalvageAuctionImages;
use App\Models\SalvageAuctionCheck;
use App\Models\SalvageAuctionImage;
use App\Models\VehicleCheck;
use App\Services\ListingImport\SafeUrlFetcher;
use App\Services\SalvageAuction\SalvageAuctionImageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetrieveSalvageAuctionImagesJobTest extends TestCase
{
    use RefreshDatabase;

    private function runJob(VehicleCheck $check, SalvageAuctionImageProvider $provider): void
    {
        (new RetrieveSalvageAuctionImages($check->id))->handle($provider, app(SafeUrlFetcher::class));
    }

    private function spyProvider(array $return = []): SalvageAuctionImageProvider
    {
        return new class($return) implements SalvageAuctionImageProvider
        {
            public int $calls = 0;

            public array $seenRecordIds = [];

            public function __construct(private readonly array $return) {}

            public function fetchImages(string $recordId, ?int $vehicleCheckId = null): array
            {
                $this->calls++;
                $this->seenRecordIds[] = $recordId;

                return $this->return;
            }
        };
    }

    public function test_it_does_nothing_when_no_salvage_record_was_found(): void
    {
        Storage::fake('local');
        $check = VehicleCheck::factory()->create();
        SalvageAuctionCheck::create(['vehicle_check_id' => $check->id, 'record_found' => false, 'records' => []]);

        $provider = $this->spyProvider();
        $this->runJob($check, $provider);

        $this->assertSame(0, $provider->calls);
        $this->assertSame(0, SalvageAuctionImage::count());
    }

    public function test_it_does_nothing_when_there_is_no_salvage_auction_check_row_at_all(): void
    {
        Storage::fake('local');
        $check = VehicleCheck::factory()->create();

        $provider = $this->spyProvider();
        $this->runJob($check, $provider);

        $this->assertSame(0, $provider->calls);
        $this->assertSame(0, SalvageAuctionImage::count());
    }

    public function test_it_downloads_images_already_returned_by_the_primary_call_without_paying_for_the_image_endpoint(): void
    {
        Storage::fake('local');
        Http::fake([
            'example.com/photo1.jpg' => Http::response('bytes-one', 200),
            'example.com/photo2.jpg' => Http::response('bytes-two', 200),
        ]);

        $check = VehicleCheck::factory()->create();
        $salvageCheck = SalvageAuctionCheck::create([
            'vehicle_check_id' => $check->id,
            'record_found' => true,
            'records' => [[
                'recordId' => 555,
                'imageUrls' => ['https://example.com/photo1.jpg', 'https://example.com/photo2.jpg'],
            ]],
        ]);

        $provider = $this->spyProvider(['https://should-not-be-used.example.com/x.jpg']);
        $this->runJob($check, $provider);

        $this->assertSame(0, $provider->calls, 'The paid endpoint must never be called when the primary call already returned images.');
        $this->assertSame(2, $salvageCheck->images()->count());
        $this->assertSame(['https://example.com/photo1.jpg', 'https://example.com/photo2.jpg'], $salvageCheck->images()->pluck('source_url')->all());

        foreach ($salvageCheck->images as $image) {
            Storage::disk('local')->assertExists($image->path);
            $this->assertSame(0, $image->record_index);
        }
    }

    public function test_it_calls_the_paid_provider_when_the_primary_call_returned_no_images(): void
    {
        Storage::fake('local');
        Http::fake([
            'example.com/fetched.jpg' => Http::response('fetched-bytes', 200),
        ]);

        $check = VehicleCheck::factory()->create();
        $salvageCheck = SalvageAuctionCheck::create([
            'vehicle_check_id' => $check->id,
            'record_found' => true,
            'records' => [[
                'recordId' => 777,
                'imageUrls' => [],
            ]],
        ]);

        $provider = $this->spyProvider(['https://example.com/fetched.jpg']);
        $this->runJob($check, $provider);

        $this->assertSame(1, $provider->calls);
        $this->assertSame(['777'], $provider->seenRecordIds);
        $this->assertSame(1, $salvageCheck->images()->count());
        $this->assertSame('https://example.com/fetched.jpg', $salvageCheck->images()->first()->source_url);
    }

    public function test_a_record_with_no_record_id_is_skipped_entirely(): void
    {
        Storage::fake('local');
        $check = VehicleCheck::factory()->create();
        SalvageAuctionCheck::create([
            'vehicle_check_id' => $check->id,
            'record_found' => true,
            'records' => [[
                'recordId' => null,
                'imageUrls' => [],
            ]],
        ]);

        $provider = $this->spyProvider(['https://example.com/should-not-be-fetched.jpg']);
        $this->runJob($check, $provider);

        $this->assertSame(0, $provider->calls);
        $this->assertSame(0, SalvageAuctionImage::count());
    }

    public function test_a_url_that_fails_to_download_is_silently_skipped(): void
    {
        Storage::fake('local');
        Http::fake([
            'example.com/good.jpg' => Http::response('good-bytes', 200),
            'example.com/bad.jpg' => Http::response('', 500),
        ]);

        $check = VehicleCheck::factory()->create();
        $salvageCheck = SalvageAuctionCheck::create([
            'vehicle_check_id' => $check->id,
            'record_found' => true,
            'records' => [[
                'recordId' => 1,
                'imageUrls' => ['https://example.com/good.jpg', 'https://example.com/bad.jpg'],
            ]],
        ]);

        $provider = $this->spyProvider();
        $this->runJob($check, $provider);

        $this->assertSame(1, $salvageCheck->images()->count());
        $this->assertSame('https://example.com/good.jpg', $salvageCheck->images()->first()->source_url);
    }

    public function test_a_network_failure_downloading_one_url_is_silently_skipped_rather_than_failing_the_job(): void
    {
        Storage::fake('local');
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $check = VehicleCheck::factory()->create();
        $salvageCheck = SalvageAuctionCheck::create([
            'vehicle_check_id' => $check->id,
            'record_found' => true,
            'records' => [[
                'recordId' => 1,
                'imageUrls' => ['https://example.com/unreachable.jpg'],
            ]],
        ]);

        $provider = $this->spyProvider();

        // Must not throw.
        $this->runJob($check, $provider);

        $this->assertSame(0, $salvageCheck->images()->count());
    }

    public function test_images_from_multiple_records_are_matched_to_the_correct_record_index(): void
    {
        Storage::fake('local');
        Http::fake([
            'example.com/record0.jpg' => Http::response('record0-bytes', 200),
            'example.com/record1.jpg' => Http::response('record1-bytes', 200),
        ]);

        $check = VehicleCheck::factory()->create();
        $salvageCheck = SalvageAuctionCheck::create([
            'vehicle_check_id' => $check->id,
            'record_found' => true,
            'records' => [
                ['recordId' => 1, 'imageUrls' => ['https://example.com/record0.jpg']],
                ['recordId' => 2, 'imageUrls' => ['https://example.com/record1.jpg']],
            ],
        ]);

        $provider = $this->spyProvider();
        $this->runJob($check, $provider);

        $this->assertSame(0, $salvageCheck->images()->where('source_url', 'https://example.com/record0.jpg')->value('record_index'));
        $this->assertSame(1, $salvageCheck->images()->where('source_url', 'https://example.com/record1.jpg')->value('record_index'));
    }
}
