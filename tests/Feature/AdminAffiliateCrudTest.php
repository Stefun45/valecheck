<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Creator;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAffiliateCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_access_any_affiliate_admin_route(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.affiliates.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.affiliates.create'))->assertForbidden();
    }

    public function test_an_admin_can_create_an_affiliate_for_an_existing_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $affiliateUser = User::factory()->create(['email' => 'affiliate@example.com']);

        $this->actingAs($admin)->post(route('admin.affiliates.store'), [
            'email' => 'affiliate@example.com',
            'name' => 'Jane Affiliate',
            'referral_code' => 'JANE10',
        ])->assertRedirect(route('admin.affiliates.index'));

        $this->assertDatabaseHas('creators', [
            'user_id' => $affiliateUser->id,
            'name' => 'Jane Affiliate',
            'referral_code' => 'JANE10',
        ]);
    }

    public function test_creating_an_affiliate_without_a_code_auto_generates_one(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['email' => 'noaffiliatecode@example.com']);

        $this->actingAs($admin)->post(route('admin.affiliates.store'), [
            'email' => 'noaffiliatecode@example.com',
            'name' => 'No Code Jane',
        ]);

        $creator = Creator::where('name', 'No Code Jane')->firstOrFail();
        $this->assertNotEmpty($creator->referral_code);
    }

    public function test_creating_an_affiliate_for_a_nonexistent_user_fails_validation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.affiliates.store'), [
            'email' => 'nobody@example.com',
            'name' => 'Ghost',
        ])->assertSessionHasErrors('email');
    }

    public function test_an_admin_can_toggle_an_affiliate_active_state(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $affiliateUser = User::factory()->create();
        $creator = Creator::create(['user_id' => $affiliateUser->id, 'name' => 'Jane', 'referral_code' => 'JANE10']);

        $this->actingAs($admin)->post(route('admin.affiliates.toggle', $creator));

        $this->assertFalse($creator->fresh()->is_active);
    }

    public function test_the_show_page_lists_referrals_and_commissions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $affiliateUser = User::factory()->create();
        $creator = Creator::create(['user_id' => $affiliateUser->id, 'name' => 'Jane', 'referral_code' => 'JANE10']);
        $buyer = User::factory()->create(['email' => 'buyer@example.com']);
        $referral = Referral::create(['creator_id' => $creator->id, 'referred_user_id' => $buyer->id, 'attributed_at' => now()]);
        Commission::create(['creator_id' => $creator->id, 'referral_id' => $referral->id, 'type' => 'one_off', 'amount' => 1.00, 'status' => Commission::STATUS_PENDING]);

        $this->actingAs($admin)
            ->get(route('admin.affiliates.show', $creator))
            ->assertOk()
            ->assertSeeText('buyer@example.com')
            ->assertSeeText('£1.00');
    }

    public function test_an_admin_can_mark_a_pending_commission_as_paid(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $affiliateUser = User::factory()->create();
        $creator = Creator::create(['user_id' => $affiliateUser->id, 'name' => 'Jane', 'referral_code' => 'JANE10']);
        $commission = Commission::create(['creator_id' => $creator->id, 'type' => 'one_off', 'amount' => 1.00, 'status' => Commission::STATUS_PENDING]);

        $this->actingAs($admin)->post(route('admin.commissions.mark-paid', $commission));

        $this->assertSame(Commission::STATUS_PAID, $commission->fresh()->status);
    }

    public function test_a_reversed_commission_cannot_be_marked_paid(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $affiliateUser = User::factory()->create();
        $creator = Creator::create(['user_id' => $affiliateUser->id, 'name' => 'Jane', 'referral_code' => 'JANE10']);
        $commission = Commission::create(['creator_id' => $creator->id, 'type' => 'one_off', 'amount' => 1.00, 'status' => Commission::STATUS_REVERSED]);

        $this->actingAs($admin)->post(route('admin.commissions.mark-paid', $commission));

        $this->assertSame(Commission::STATUS_REVERSED, $commission->fresh()->status);
    }
}
