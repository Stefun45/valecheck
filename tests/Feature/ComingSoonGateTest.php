<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComingSoonGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_the_coming_soon_page_on_the_homepage(): void
    {
        config(['valecheck.coming_soon_enabled' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSeeText('Coming Soon')
            ->assertDontSeeText('Which Check Do You Need?');
    }

    public function test_a_logged_in_user_sees_the_real_homepage(): void
    {
        config(['valecheck.coming_soon_enabled' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertDontSeeText('Coming Soon')
            ->assertSeeText('Which Check Do You Need?');
    }

    public function test_a_guest_is_redirected_away_from_any_other_page_to_the_homepage(): void
    {
        config(['valecheck.coming_soon_enabled' => true]);

        $this->get('/check')->assertRedirect(route('welcome'));
        $this->get('/reports')->assertRedirect(route('welcome'));
    }

    public function test_registration_is_closed_for_a_guest_while_the_gate_is_on(): void
    {
        config(['valecheck.coming_soon_enabled' => true]);

        $this->get('/register')->assertRedirect(route('welcome'));
    }

    public function test_login_forgot_password_and_reset_password_stay_reachable_for_a_guest(): void
    {
        config(['valecheck.coming_soon_enabled' => true]);

        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/some-token')->assertOk();
    }

    public function test_terms_and_privacy_stay_reachable_for_a_guest(): void
    {
        config(['valecheck.coming_soon_enabled' => true]);

        $this->get('/terms')->assertOk();
        $this->get('/privacy')->assertOk();
    }

    public function test_the_stripe_webhook_and_health_check_stay_reachable_for_a_guest(): void
    {
        config(['valecheck.coming_soon_enabled' => true]);

        $response = $this->postJson('stripe/webhook', ['type' => 'unknown.event']);
        $this->assertNotSame(302, $response->getStatusCode());

        $this->get('/up')->assertOk();
    }

    public function test_the_gate_is_a_complete_no_op_when_disabled(): void
    {
        config(['valecheck.coming_soon_enabled' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSeeText('Which Check Do You Need?');

        $this->get('/check')->assertOk();
    }
}
