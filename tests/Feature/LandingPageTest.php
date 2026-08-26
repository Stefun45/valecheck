<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_hero_and_pricing(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeText('KNOW BEFORE YOU BUY')
            ->assertSeeText('£8.99')
            ->assertSeeText('£14.99');
    }

    public function test_a_guest_can_find_a_direct_link_to_register(): void
    {
        // A direct "Register" link must always exist somewhere a guest can
        // reach without first going through the whole vehicle-check wizard.
        $this->get('/')
            ->assertOk()
            ->assertSee(route('register'), false);
    }

    public function test_a_guest_can_visit_the_check_flow_from_the_registration_prefilled_directly(): void
    {
        // Guests can browse pricing/options without an account — signing up
        // is only required once they actually try to get a report (covered
        // in VehicleCheckFlowTest::test_a_guest_is_prompted_to_sign_up_only_at_the_point_of_submitting).
        $this->get('/check?registration=AB12CDE')
            ->assertOk()
            ->assertSeeText('AB12CDE');
    }
}
