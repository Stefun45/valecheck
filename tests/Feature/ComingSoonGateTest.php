<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
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

    public function test_livewires_own_update_endpoint_is_never_redirected_for_a_guest(): void
    {
        // Real production bug: every Livewire component action (including
        // clicking "Log In" itself) is an AJAX POST to livewire/update,
        // not a real navigation to /login. Volt::test()/Livewire::test()
        // call component methods directly and never exercise this route
        // at all, so they can't catch this - hitting the actual endpoint
        // through the real HTTP kernel is the only way to.
        config(['valecheck.coming_soon_enabled' => true]);

        $response = $this->post('/livewire/update', []);

        $this->assertNotSame(302, $response->getStatusCode());
        $this->assertNotSame(route('welcome'), $response->headers->get('Location'));
    }

    public function test_a_real_login_survives_the_gate_on_the_very_next_request(): void
    {
        // Deliberately not actingAs() — that sets the auth guard directly
        // without touching the session at all. This exercises the real
        // login flow instead, which is closer to a genuine browser
        // session. Note: this test still passes even with the gate
        // wrongly prepended (verified by reverting it locally) — Laravel's
        // test kernel doesn't reproduce the real HTTP session-timing bug
        // that caused a production login loop, so this doesn't guarantee
        // against that specific ordering mistake recurring. The real fix
        // is the append (not prepend) in bootstrap/app.php, which must run
        // after the web group's own session-starting middleware.
        config(['valecheck.coming_soon_enabled' => true]);
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->get('/')
            ->assertOk()
            ->assertDontSeeText('Coming Soon');

        $this->get('/dashboard')->assertOk();
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
