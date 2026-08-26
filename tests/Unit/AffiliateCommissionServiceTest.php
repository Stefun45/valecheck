<?php

namespace Tests\Unit;

use App\Services\Affiliate\AffiliateCommissionService;
use Tests\TestCase;

class AffiliateCommissionServiceTest extends TestCase
{
    public function test_commission_is_based_on_the_correct_product(): void
    {
        $service = new AffiliateCommissionService;

        $this->assertSame(1.00, $service->commissionFor('check'));
        $this->assertSame(1.50, $service->commissionFor('plus'));
        $this->assertSame(2.00, $service->commissionFor('rebuild'));
    }

    public function test_commission_is_based_on_the_correct_subscription_plan(): void
    {
        $service = new AffiliateCommissionService;

        $this->assertSame(5.00, $service->commissionFor('trader'));
        $this->assertSame(7.50, $service->commissionFor('pro'));
        $this->assertSame(15.00, $service->commissionFor('dealer'));
    }

    public function test_unknown_product_returns_zero_rather_than_guessing(): void
    {
        $service = new AffiliateCommissionService;

        $this->assertSame(0.0, $service->commissionFor('made-up-product'));
    }
}
