<?php

namespace Tests\Feature;

use App\Models\SubscriptionUsage;
use App\Models\TraderVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraderVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['valecheck.subscriptions_enabled' => true]);
    }

    private function subscribeToPlan(User $user, string $plan): void
    {
        SubscriptionUsage::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'report_type' => 'plus',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'allowance' => 30,
            'used' => 0,
        ]);
    }

    public function test_a_dealer_subscriber_can_submit_business_details(): void
    {
        $user = User::factory()->create();
        $this->subscribeToPlan($user, 'dealer');
        $this->actingAs($user);

        $this->post(route('billing.trader-verification.store'), [
            'company_name' => 'Test Motors Ltd',
            'company_number' => '12345678',
            'vat_number' => 'GB123456789',
        ])->assertRedirect(route('dashboard'));

        $verification = TraderVerification::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Test Motors Ltd', $verification->company_name);
        $this->assertSame(TraderVerification::STATUS_PENDING, $verification->status);
        $this->assertFalse($user->fresh()->hasVerifiedTradeAccess());
    }

    public function test_resubmitting_after_rejection_reopens_the_same_row_as_pending(): void
    {
        $user = User::factory()->create();
        $this->subscribeToPlan($user, 'dealer');
        TraderVerification::create([
            'user_id' => $user->id,
            'company_name' => 'Old Name Ltd',
            'company_number' => '00000000',
            'status' => TraderVerification::STATUS_REJECTED,
            'notes' => 'Company number did not match Companies House.',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now()->subDay(),
        ]);
        $this->actingAs($user);

        $this->post(route('billing.trader-verification.store'), [
            'company_name' => 'Corrected Motors Ltd',
            'company_number' => '87654321',
        ]);

        $this->assertSame(1, TraderVerification::where('user_id', $user->id)->count());
        $verification = TraderVerification::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Corrected Motors Ltd', $verification->company_name);
        $this->assertSame(TraderVerification::STATUS_PENDING, $verification->status);
        $this->assertNull($verification->notes);
        $this->assertNull($verification->reviewed_at);
    }

    public function test_the_submission_endpoint_is_unreachable_when_subscriptions_are_disabled(): void
    {
        config(['valecheck.subscriptions_enabled' => false]);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('billing.trader-verification.store'), [
            'company_name' => 'Test Motors Ltd',
            'company_number' => '12345678',
        ])->assertNotFound();
    }

    public function test_a_non_admin_cannot_view_the_admin_review_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $this->get(route('admin.trader-verifications.index'))->assertForbidden();
    }

    public function test_an_admin_can_approve_a_pending_verification(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $trader = User::factory()->create();
        $this->subscribeToPlan($trader, 'dealer');
        $verification = TraderVerification::create([
            'user_id' => $trader->id,
            'company_name' => 'Test Motors Ltd',
            'company_number' => '12345678',
            'status' => TraderVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.trader-verifications.approve', $verification))
            ->assertRedirect();

        $verification->refresh();
        $this->assertSame(TraderVerification::STATUS_APPROVED, $verification->status);
        $this->assertSame($admin->id, $verification->reviewed_by);
        $this->assertNotNull($verification->reviewed_at);
        $this->assertTrue($trader->fresh()->hasVerifiedTradeAccess());
    }

    public function test_an_admin_can_reject_a_pending_verification_with_a_reason(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $trader = User::factory()->create();
        $verification = TraderVerification::create([
            'user_id' => $trader->id,
            'company_name' => 'Test Motors Ltd',
            'company_number' => '12345678',
            'status' => TraderVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.trader-verifications.reject', $verification), ['notes' => 'Company number invalid.'])
            ->assertRedirect();

        $verification->refresh();
        $this->assertSame(TraderVerification::STATUS_REJECTED, $verification->status);
        $this->assertSame('Company number invalid.', $verification->notes);
    }

    public function test_rejecting_without_a_reason_is_rejected_by_validation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $verification = TraderVerification::create([
            'user_id' => User::factory()->create()->id,
            'company_name' => 'Test Motors Ltd',
            'company_number' => '12345678',
            'status' => TraderVerification::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.trader-verifications.reject', $verification), [])
            ->assertSessionHasErrors('notes');
    }
}
