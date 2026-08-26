<?php

namespace Tests\Unit;

use App\DataTransferObjects\ListingImportResult;
use App\Services\ListingImport\CopartListingProvider;
use App\Services\ListingImport\SafeUrlFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CopartListingProviderTest extends TestCase
{
    public function test_it_recognises_copart_urls(): void
    {
        $provider = new CopartListingProvider(new SafeUrlFetcher);

        $this->assertTrue($provider->supports('https://www.copart.co.uk/lot/12345678'));
        $this->assertFalse($provider->supports('https://www.ebay.co.uk/itm/12345'));
    }

    public function test_it_genuinely_attempts_a_fetch_unlike_ebay_and_autotrader(): void
    {
        Http::fake(['copart.co.uk/*' => Http::response('<html><body>No metadata here.</body></html>', 200)]);

        $result = (new CopartListingProvider(new SafeUrlFetcher))->import('https://www.copart.co.uk/lot/12345678');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'copart.co.uk'));
        $this->assertSame(ListingImportResult::STATUS_FAILED, $result->status);
    }

    public function test_a_path_disallowed_by_copart_robots_txt_is_declined_without_fetching(): void
    {
        Http::fake();

        $result = (new CopartListingProvider(new SafeUrlFetcher))->import('https://www.copart.co.uk/dashboard/myaccount');

        $this->assertSame(ListingImportResult::STATUS_BLOCKED, $result->status);
        Http::assertNothingSent();
    }
}
