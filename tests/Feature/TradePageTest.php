<?php

namespace Tests\Feature;

use App\Mail\TradeInterestEmail;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TradePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_trade_page_renders_for_a_guest(): void
    {
        $this->get(route('trade'))
            ->assertOk()
            ->assertSeeText('Trade')
            ->assertSeeText('Trade Verification')
            ->assertSeeText('Trade Credit Plans');
    }

    public function test_the_trade_page_lists_every_active_subscription_plan_with_its_real_price_and_credits(): void
    {
        $plan = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->first();

        $this->get(route('trade'))
            ->assertOk()
            ->assertSeeText($plan->name)
            ->assertSeeText(number_format($plan->monthly_net, 2))
            ->assertSeeText(number_format($plan->monthly_credits));
    }

    public function test_an_inactive_plan_is_not_shown(): void
    {
        $plan = SubscriptionPlan::first();
        $plan->update(['is_active' => false]);

        $this->get(route('trade'))->assertDontSeeText($plan->name);
    }

    public function test_submitting_the_interest_form_emails_the_configured_address(): void
    {
        Mail::fake();
        config(['valecheck.enterprise_contact_email' => 'sales@example.com']);

        $this->post(route('trade.interest.submit'), [
            'name' => 'Jane Trader',
            'email' => 'jane@example.com',
            'company' => 'Jane Motors Ltd',
            'message' => 'We check around 50 cars a month.',
        ])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertQueued(TradeInterestEmail::class, function (TradeInterestEmail $mail) {
            return $mail->hasTo('sales@example.com')
                && $mail->enquiry['name'] === 'Jane Trader'
                && $mail->enquiry['email'] === 'jane@example.com';
        });
    }

    public function test_submitting_the_interest_form_does_not_require_an_account(): void
    {
        Mail::fake();

        $this->assertGuest();

        $this->post(route('trade.interest.submit'), [
            'name' => 'Jane Trader',
            'email' => 'jane@example.com',
        ])->assertRedirect();

        Mail::assertQueued(TradeInterestEmail::class);
    }

    public function test_submitting_without_a_name_or_email_fails_validation(): void
    {
        $this->post(route('trade.interest.submit'), ['name' => '', 'email' => ''])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_the_trade_page_stays_reachable_while_the_coming_soon_gate_is_on(): void
    {
        config(['valecheck.coming_soon_enabled' => true]);

        $this->get(route('trade'))->assertOk();
    }
}
