<?php

namespace Tests\Unit;

use App\Services\SalvageAuction\MockSalvageAuctionProvider;
use Tests\TestCase;

class MockSalvageAuctionProviderTest extends TestCase
{
    public function test_it_is_deterministic_for_the_same_registration(): void
    {
        $provider = new MockSalvageAuctionProvider;

        $first = $provider->check('AB12CDE');
        $second = $provider->check('AB12CDE');

        $this->assertSame($first->recordFound, $second->recordFound);
        $this->assertSame($first->records, $second->records);
    }

    public function test_a_found_record_never_uses_a_real_looking_image_url(): void
    {
        $provider = new MockSalvageAuctionProvider;

        $result = $provider->check('EE01AAA');

        $this->assertTrue($result->recordFound);
        foreach ($result->records[0]['imageUrls'] as $imageUrl) {
            $this->assertStringContainsString('placehold.co', $imageUrl);
        }
    }

    public function test_most_registrations_have_no_salvage_record(): void
    {
        $provider = new MockSalvageAuctionProvider;

        $result = $provider->check('CD34EFG');

        $this->assertFalse($result->recordFound);
        $this->assertSame([], $result->records);
    }
}
