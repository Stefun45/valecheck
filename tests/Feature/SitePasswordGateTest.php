<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use App\Models\VehicleCheck;
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

    public function test_a_whitelisted_ip_skips_the_password_entirely(): void
    {
        config([
            'valecheck.site_password' => 'letmein',
            'valecheck.site_password_ip_whitelist' => ['77.98.90.235'],
        ]);

        $this->call('GET', '/', server: ['REMOTE_ADDR' => '77.98.90.235'])
            ->assertOk();
    }

    public function test_a_non_whitelisted_ip_is_still_gated(): void
    {
        config([
            'valecheck.site_password' => 'letmein',
            'valecheck.site_password_ip_whitelist' => ['77.98.90.235'],
        ]);

        $this->call('GET', '/', server: ['REMOTE_ADDR' => '1.2.3.4'])
            ->assertUnauthorized();
    }

    public function test_terms_and_privacy_are_never_gated_even_with_a_password_set(): void
    {
        config(['valecheck.site_password' => 'letmein']);

        $this->get('/terms')->assertOk();
        $this->get('/privacy')->assertOk();
    }

    public function test_the_exempt_reports_web_view_and_pdf_skip_the_password(): void
    {
        $user = User::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => $check->type, 'headline_summary' => 'Test.']);

        config([
            'valecheck.site_password' => 'letmein',
            'valecheck.site_password_exempt_report' => $check->public_id,
        ]);

        // Still requires login (a separate barrier from the site password),
        // so an unauthenticated hit redirects to login rather than 401ing.
        $this->get("/checks/{$check->public_id}")->assertRedirect(route('login'));
        $this->get("/checks/{$check->public_id}/pdf")->assertRedirect(route('login'));
    }

    public function test_a_different_reports_web_view_is_still_gated(): void
    {
        $user = User::factory()->create();
        $exemptCheck = VehicleCheck::factory()->create(['user_id' => $user->id]);
        $otherCheck = VehicleCheck::factory()->create(['user_id' => $user->id]);

        config([
            'valecheck.site_password' => 'letmein',
            'valecheck.site_password_exempt_report' => $exemptCheck->public_id,
        ]);

        $this->get("/checks/{$otherCheck->public_id}")->assertUnauthorized();
    }
}
