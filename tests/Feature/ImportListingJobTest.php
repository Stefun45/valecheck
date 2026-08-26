<?php

namespace Tests\Feature;

use App\Jobs\ImportListing;
use App\Models\ListingImage;
use App\Models\ListingImport;
use App\Services\ListingImport\ListingImportService;
use App\Services\ListingImport\SafeUrlFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportListingJobTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingImport(string $url = 'https://example.com/listing/1'): ListingImport
    {
        return ListingImport::create([
            'url' => $url,
            'url_hash' => sha1($url),
            'domain' => parse_url($url, PHP_URL_HOST),
            'provider' => 'pending',
            'status' => ListingImport::STATUS_PENDING,
        ]);
    }

    private function runJob(ListingImport $listingImport): void
    {
        (new ImportListing($listingImport->id))->handle(
            app(ListingImportService::class),
            app(SafeUrlFetcher::class),
        );

        $listingImport->refresh();
    }

    public function test_a_successful_import_downloads_images_and_stores_fields(): void
    {
        $html = <<<'HTML'
            <html><head>
            <script type="application/ld+json">
            {"@type": "Car", "name": "2019 BMW M4", "brand": {"name": "BMW"}, "model": "M4", "mileageFromOdometer": {"value": 82000}, "offers": {"price": "7500"}, "image": ["https://example.com/img1.jpg", "https://example.com/img2.jpg"]}
            </script>
            </head><body></body></html>
            HTML;

        Http::fake([
            'example.com/listing/1' => Http::response($html, 200),
            'example.com/img1.jpg' => Http::response('bytes-one', 200),
            'example.com/img2.jpg' => Http::response('bytes-two', 200),
        ]);

        $listingImport = $this->makePendingImport();
        $this->runJob($listingImport);

        $this->assertSame(ListingImport::STATUS_SUCCESS, $listingImport->status);
        $this->assertSame('generic', $listingImport->provider);
        $this->assertSame('BMW', $listingImport->data['make']['value']);
        $this->assertSame(2, $listingImport->images()->count());
        $this->assertSame(2, $listingImport->images()->where('status', ListingImage::STATUS_DOWNLOADED)->count());
    }

    public function test_duplicate_image_bytes_are_deduplicated(): void
    {
        $html = <<<'HTML'
            <html><head>
            <script type="application/ld+json">
            {"@type": "Car", "name": "2019 BMW M4", "image": ["https://example.com/img1.jpg", "https://example.com/img2.jpg"]}
            </script>
            </head></html>
            HTML;

        Http::fake([
            'example.com/listing/1' => Http::response($html, 200),
            'example.com/img1.jpg' => Http::response('identical-bytes', 200),
            'example.com/img2.jpg' => Http::response('identical-bytes', 200),
        ]);

        $listingImport = $this->makePendingImport();
        $this->runJob($listingImport);

        $this->assertSame(1, $listingImport->images()->where('status', ListingImage::STATUS_DOWNLOADED)->count());
        $this->assertSame(1, $listingImport->images()->where('status', ListingImage::STATUS_SKIPPED_DUPLICATE)->count());
    }

    public function test_images_beyond_the_configured_cap_are_skipped_and_flagged(): void
    {
        config(['valecheck.listing_import.max_images' => 2]);

        $imageUrls = collect(range(1, 4))->map(fn ($n) => "https://example.com/img{$n}.jpg");
        $jsonImages = json_encode($imageUrls->all());

        $html = "<html><head><script type=\"application/ld+json\">{\"@type\":\"Car\",\"name\":\"Test\",\"image\":{$jsonImages}}</script></head></html>";

        $fakes = ['example.com/listing/1' => Http::response($html, 200)];

        foreach ($imageUrls as $i => $url) {
            $fakes[str_replace('https://', '', $url)] = Http::response("bytes-{$i}", 200);
        }

        Http::fake($fakes);

        $listingImport = $this->makePendingImport();
        $this->runJob($listingImport);

        $this->assertTrue((bool) $listingImport->images_capped);
        $this->assertSame(2, $listingImport->images()->where('status', ListingImage::STATUS_DOWNLOADED)->count());
        $this->assertSame(2, $listingImport->images()->where('status', ListingImage::STATUS_SKIPPED_OVER_LIMIT)->count());
    }

    public function test_a_blocked_domain_never_attempts_to_download_images(): void
    {
        Http::fake();

        $listingImport = $this->makePendingImport('https://www.ebay.co.uk/itm/123');
        $this->runJob($listingImport);

        $this->assertSame(ListingImport::STATUS_BLOCKED, $listingImport->status);
        $this->assertSame(0, $listingImport->images()->count());
        Http::assertNothingSent();
    }
}
