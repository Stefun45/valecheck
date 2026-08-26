<?php

namespace Tests\Unit;

use App\DataTransferObjects\ListingImportResult;
use App\Services\ListingImport\AutoTraderListingProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AutoTraderListingProviderTest extends TestCase
{
    public function test_it_recognises_autotrader_urls(): void
    {
        $provider = new AutoTraderListingProvider;

        $this->assertTrue($provider->supports('https://www.autotrader.co.uk/car-details/12345'));
        $this->assertFalse($provider->supports('https://www.ebay.co.uk/itm/12345'));
    }

    public function test_it_declines_to_import_without_ever_sending_a_request(): void
    {
        Http::fake();

        $result = (new AutoTraderListingProvider)->import('https://www.autotrader.co.uk/car-details/12345');

        $this->assertSame(ListingImportResult::STATUS_BLOCKED, $result->status);
        Http::assertNothingSent();
    }
}
