<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_view_the_plans_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.subscription-plans.index'))->assertForbidden();
    }

    public function test_an_admin_can_view_the_seeded_launch_plans(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.subscription-plans.index'))
            ->assertOk()
            // These only appear as input value="..." attributes, not
            // rendered text content, so a raw HTML search is needed.
            ->assertSee('Pro 25')
            ->assertSee('Dealer 1,000');
    }

    public function test_an_admin_can_create_a_new_plan(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.subscription-plans.store'), [
            'name' => 'Dealer 2,000',
            'group' => 'dealer',
            'stripe_price_id' => 'price_dealer_2000',
            'monthly_net' => 3999.00,
            'monthly_credits' => 2000,
            'additional_credit_net' => 3.19,
            'sort_order' => 7,
            'is_active' => '1',
        ])->assertRedirect(route('admin.subscription-plans.index'));

        $plan = SubscriptionPlan::where('name', 'Dealer 2,000')->firstOrFail();
        $this->assertSame('dealer', $plan->group);
        $this->assertSame(2000, $plan->monthly_credits);
        $this->assertTrue($plan->is_active);
    }

    public function test_an_admin_can_update_a_plan_and_deactivate_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $plan = SubscriptionPlan::where('name', 'Pro 25')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.subscription-plans.update', $plan), [
            'name' => 'Pro 25',
            'group' => 'pro',
            'stripe_price_id' => $plan->stripe_price_id,
            'monthly_net' => 109.00,
            'monthly_credits' => 25,
            'additional_credit_net' => 4.99,
            'sort_order' => $plan->sort_order,
            // is_active omitted - an unchecked checkbox sends nothing.
        ])->assertRedirect(route('admin.subscription-plans.index'));

        $plan->refresh();
        $this->assertSame(109.00, (float) $plan->monthly_net);
        $this->assertFalse($plan->is_active);
    }

    public function test_creating_a_plan_requires_the_core_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.subscription-plans.store'), [])
            ->assertSessionHasErrors(['name', 'monthly_net', 'monthly_credits', 'additional_credit_net', 'sort_order']);
    }
}
