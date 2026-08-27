<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionsHiddenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['valecheck.subscriptions_enabled' => false]);
    }

    public function test_the_dashboard_hides_subscriptions_but_still_shows_plus_credits(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Subscribe for regular checks')
            ->assertDontSeeText('Enterprise')
            ->assertDontSeeText('Subscription')
            ->assertSeeText('Buy ValeCheck Plus credits');
    }

    public function test_the_subscription_endpoint_is_unreachable(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('billing.subscribe'), ['plan' => 'trader'])->assertNotFound();
    }

    public function test_the_credit_pack_endpoint_remains_reachable(): void
    {
        // Credit packs are unaffected by this flag — must not 404. It still
        // redirects back (Stripe isn't configured in tests), which is
        // enough to prove the route was actually reached.
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('billing.credit-pack'), ['pack' => 'plus_1'])->assertStatus(302);
    }
}
