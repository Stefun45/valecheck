<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePasswordGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_site_is_fully_open_when_no_password_is_configured(): void
    {
        config(['valecheck.site_password' => null]);

        $this->get('/')->assertOk();
    }

    public function test_a_request_with_no_credentials_is_rejected_when_a_password_is_set(): void
    {
        config(['valecheck.site_password' => 'letmein']);

        $this->get('/')->assertUnauthorized();
    }

    public function test_the_wrong_password_is_rejected(): void
    {
        config(['valecheck.site_password' => 'letmein']);

        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('valecheck:wrong-password')])
            ->get('/')
            ->assertUnauthorized();
    }

    public function test_the_correct_password_is_accepted_regardless_of_username(): void
    {
        config(['valecheck.site_password' => 'letmein']);

        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('anyone:letmein')])
            ->get('/')
            ->assertOk();
    }

    public function test_the_stripe_webhook_is_never_gated_even_with_a_password_set(): void
    {
        // Stripe's servers hit this, not a browser — they can't answer a
        // Basic Auth prompt, so this must always stay reachable.
        config(['valecheck.site_password' => 'letmein']);

        $response = $this->postJson('stripe/webhook', ['type' => 'unknown.event']);

        $this->assertNotSame(401, $response->getStatusCode());
    }

    public function test_the_health_check_route_is_never_gated_even_with_a_password_set(): void
    {
        // Laravel Cloud polls this to know the app is alive — it can't
        // answer a Basic Auth prompt either.
        config(['valecheck.site_password' => 'letmein']);

        $this->get('/up')->assertOk();
    }
}
