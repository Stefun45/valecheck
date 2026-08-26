<?php

namespace Tests\Unit;

use App\DataTransferObjects\ListingImportResult;
use App\Services\ListingImport\GenericPublicListingProvider;
use App\Services\ListingImport\SafeUrlFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenericPublicListingProviderTest extends TestCase
{
    public function test_supports_any_http_or_https_url(): void
    {
        $provider = new GenericPublicListingProvider(new SafeUrlFetcher);

        $this->assertTrue($provider->supports('https://example.com/listing/1'));
        $this->assertTrue($provider->supports('http://example.com/listing/1'));
        $this->assertFalse($provider->supports('ftp://example.com/listing/1'));
    }

    public function test_a_json_ld_vehicle_listing_is_parsed_successfully(): void
    {
        $html = <<<'HTML'
            <html><head>
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Car",
                "name": "2019 BMW M4 Competition",
                "description": "Damaged repairable salvage car, runs and drives.",
                "brand": {"name": "BMW"},
                "model": "M4",
                "vehicleModelDate": "2019",
                "vehicleIdentificationNumber": "WBS12345678901234",
                "mileageFromOdometer": {"value": 82000},
                "color": "Grey",
                "vehicleTransmission": "Automatic",
                "fuelType": "Petrol",
                "image": ["https://example.com/img1.jpg", "https://example.com/img2.jpg"],
                "offers": {"price": "7500", "priceCurrency": "GBP"}
            }
            </script>
            <meta property="og:image" content="https://example.com/img3.jpg">
            </head><body></body></html>
            HTML;

        Http::fake(['example.com/*' => Http::response($html, 200, ['Content-Type' => 'text/html'])]);

        $provider = new GenericPublicListingProvider(new SafeUrlFetcher);
        $result = $provider->import('https://example.com/listing/1');

        $this->assertSame(ListingImportResult::STATUS_SUCCESS, $result->status);
        $this->assertSame('generic', $result->provider);

        $this->assertTrue($result->fields['make']['found']);
        $this->assertSame('BMW', $result->fields['make']['value']);
        $this->assertSame('M4', $result->fields['model']['value']);
        $this->assertSame(82000, $result->fields['mileage']['value']);
        $this->assertSame(7500.0, $result->fields['asking_price']['value']);
        $this->assertTrue($result->fields['vin']['found']);
        $this->assertSame('WBS12345678901234', $result->fields['vin']['value']);

        // Fields genuinely absent from the source are reported as not found,
        // never guessed at.
        $this->assertFalse($result->fields['derivative']['found']);

        $this->assertCount(3, $result->images);
    }

    public function test_a_page_with_no_structured_metadata_is_reported_as_failed(): void
    {
        Http::fake(['example.com/*' => Http::response('<html><body><p>Nothing useful here.</p></body></html>', 200)]);

        $provider = new GenericPublicListingProvider(new SafeUrlFetcher);
        $result = $provider->import('https://example.com/empty');

        $this->assertSame(ListingImportResult::STATUS_FAILED, $result->status);
    }

    public function test_a_non_2xx_response_is_reported_as_failed_with_the_status_code(): void
    {
        Http::fake(['example.com/*' => Http::response('Not Found', 404)]);

        $provider = new GenericPublicListingProvider(new SafeUrlFetcher);
        $result = $provider->import('https://example.com/gone');

        $this->assertSame(ListingImportResult::STATUS_FAILED, $result->status);
        $this->assertSame(404, $result->httpStatus);
    }
}
