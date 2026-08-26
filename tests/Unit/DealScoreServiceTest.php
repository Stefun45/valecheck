<?php

namespace Tests\Unit;

use App\Services\Bidding\DealScoreService;
use App\Services\Bidding\MaximumBidService;
use Tests\TestCase;

class DealScoreServiceTest extends TestCase
{
    public function test_ideal_scenario_scores_the_maximum_100_and_recommends_buy(): void
    {
        $bid = (new MaximumBidService)->calculate(expectedResaleValue: 10000, repairCost: 1000);

        $result = (new DealScoreService)->score(
            currentPrice: 0,
            bid: $bid,
            damageFindings: [],
            marketConfidence: 'high',
            aiConfidence: 'high',
            imagesAnalysed: 10,
        );

        $this->assertSame(100, $result->score);
        $this->assertSame('buy', $result->recommendation);
    }

    public function test_paying_at_the_maximum_acquisition_price_scores_zero_margin(): void
    {
        $bid = (new MaximumBidService)->calculate(expectedResaleValue: 10000, repairCost: 1000);

        $result = (new DealScoreService)->score(
            currentPrice: $bid->maximumAcquisitionPrice,
            bid: $bid,
            damageFindings: [],
            marketConfidence: 'high',
            aiConfidence: 'high',
            imagesAnalysed: 10,
        );

        // margin sub-score should be 0 when there's no headroom left, dropping the total by exactly the margin weight (40)
        $this->assertSame(60, $result->score);
    }

    public function test_poor_economics_result_in_walk_away(): void
    {
        $bid = (new MaximumBidService)->calculate(expectedResaleValue: 2000, repairCost: 5000);

        $result = (new DealScoreService)->score(
            currentPrice: 1000,
            bid: $bid,
            damageFindings: [],
            marketConfidence: 'low',
            aiConfidence: 'low',
            imagesAnalysed: 0,
        );

        $this->assertSame('walk_away', $result->recommendation);
    }
}
