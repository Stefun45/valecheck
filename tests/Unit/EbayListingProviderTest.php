<?php

namespace Tests\Unit;

use App\DataTransferObjects\ListingImportResult;
use App\Services\ListingImport\EbayListingProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EbayListingProviderTest extends TestCase
{
    public function test_it_recognises_ebay_urls(): void
    {
        $provider = new EbayListingProvider;

        $this->assertTrue($provider->supports('https://www.ebay.co.uk/itm/12345'));
        $this->assertTrue($provider->supports('https://www.ebay.com/itm/12345'));
        $this->assertFalse($provider->supports('https://www.autotrader.co.uk/car-details/12345'));
    }

    public function test_it_declines_to_import_without_ever_sending_a_request(): void
    {
        Http::fake();

        $result = (new EbayListingProvider)->import('https://www.ebay.co.uk/itm/12345');

        $this->assertSame(ListingImportResult::STATUS_BLOCKED, $result->status);
        Http::assertNothingSent();
    }
}
