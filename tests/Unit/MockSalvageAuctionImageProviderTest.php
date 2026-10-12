<?php

namespace Tests\Unit;

use App\Services\SalvageAuction\MockSalvageAuctionImageProvider;
use Tests\TestCase;

class MockSalvageAuctionImageProviderTest extends TestCase
{
    public function test_it_unconditionally_returns_placeholder_image_urls(): void
    {
        $provider = new MockSalvageAuctionImageProvider;

        $images = $provider->fetchImages('1234567');

        $this->assertNotEmpty($images);
        foreach ($images as $imageUrl) {
            $this->assertStringContainsString('placehold.co', $imageUrl);
        }
    }
}
