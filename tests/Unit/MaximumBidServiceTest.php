<?php

namespace Tests\Unit;

use App\Services\Bidding\MaximumBidService;
use Tests\TestCase;

class MaximumBidServiceTest extends TestCase
{
    public function test_calculate_matches_hand_calculated_totals(): void
    {
        $result = (new MaximumBidService)->calculate(expectedResaleValue: 10000, repairCost: 1000);

        // fees 6% of 10000 = 600; transport 250; service/MOT 300; contingency 500; margin 15% of 10000 = 1500
        // max = 10000 - 1000 - 600 - 250 - 300 - 500 - 1500 = 5850
        $this->assertSame(600.0, $result->auctionFees);
        $this->assertSame(1500.0, $result->requiredMargin);
        $this->assertSame(5850.0, $result->maximumAcquisitionPrice);
        $this->assertSame(5850.0, $result->absoluteMaximum);
        // recommended bid is 8% below the maximum: 5850 * 0.92 = 5382
        $this->assertSame(5382.0, $result->recommendedBid);
    }

    public function test_negative_economics_are_floored_at_zero_for_the_bid_ceiling(): void
    {
        $result = (new MaximumBidService)->calculate(expectedResaleValue: 2000, repairCost: 5000);

        $this->assertSame(0.0, $result->absoluteMaximum);
        $this->assertSame(0.0, $result->recommendedBid);
    }
}
