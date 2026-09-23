<?php

namespace Tests\Feature;

use App\Livewire\VehicleCheck\ShowCheck;
use App\Models\Report;
use App\Models\SubscriptionUsage;
use App\Models\TraderVerification;
use App\Models\User;
use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HighRiskDataVisibilityTest extends TestCase
{
    use RefreshDatabase;

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

    private function verify(User $user, string $status = TraderVerification::STATUS_APPROVED): void
    {
        TraderVerification::create([
            'user_id' => $user->id,
            'company_name' => 'Test Motors Ltd',
            'company_number' => '12345678',
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    private function verifiedDealer(): User
    {
        $user = User::factory()->create();
        $this->subscribeToPlan($user, 'dealer');
        $this->verify($user);

        return $user;
    }

    public function test_a_user_has_verified_trade_access_only_with_both_an_active_plan_and_approval(): void
    {
        $verifiedDealer = $this->verifiedDealer();
        $this->assertTrue($verifiedDealer->hasVerifiedTradeAccess());

        $verifiedTrader = User::factory()->create();
        $this->subscribeToPlan($verifiedTrader, 'trader');
        $this->verify($verifiedTrader);
        $this->assertTrue($verifiedTrader->hasVerifiedTradeAccess());

        $unverifiedDealer = User::factory()->create();
        $this->subscribeToPlan($unverifiedDealer, 'dealer');
        $this->assertFalse($unverifiedDealer->hasVerifiedTradeAccess());

        $rejectedDealer = User::factory()->create();
        $this->subscribeToPlan($rejectedDealer, 'dealer');
        $this->verify($rejectedDealer, TraderVerification::STATUS_REJECTED);
        $this->assertFalse($rejectedDealer->hasVerifiedTradeAccess());

        $verifiedButUnsubscribed = User::factory()->create();
        $this->verify($verifiedButUnsubscribed);
        $this->assertFalse($verifiedButUnsubscribed->hasVerifiedTradeAccess());

        $verifiedPro = User::factory()->create();
        $this->subscribeToPlan($verifiedPro, 'pro');
        $this->verify($verifiedPro);
        $this->assertFalse($verifiedPro->hasVerifiedTradeAccess());

        $nothing = User::factory()->create();
        $this->assertFalse($nothing->hasVerifiedTradeAccess());
    }

    public function test_an_expired_dealer_subscription_does_not_grant_access_even_when_verified(): void
    {
        $user = User::factory()->create();
        SubscriptionUsage::create([
            'user_id' => $user->id,
            'plan' => 'dealer',
            'report_type' => 'plus',
            'period_start' => now()->subMonths(2)->startOfMonth(),
            'period_end' => now()->subMonths(2)->endOfMonth(),
            'allowance' => 30,
            'used' => 0,
        ]);
        $this->verify($user);

        $this->assertFalse($user->fresh()->hasVerifiedTradeAccess());
    }

    public function test_high_risk_data_is_hidden_from_an_ordinary_consumer_on_a_check_report(): void
    {
        $user = User::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);
        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false, 'high_risk_marker' => true]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertDontSeeText('High Risk')
            ->assertDontSeeText('High risk marker found');

        $pdfHtml = view('pdf.check-report', ['check' => $check->fresh()])->render();
        $this->assertStringNotContainsString('High Risk', $pdfHtml);
        $this->assertStringNotContainsString('High risk marker found', $pdfHtml);
    }

    public function test_high_risk_data_is_hidden_from_a_dealer_subscriber_who_is_not_yet_verified(): void
    {
        $user = User::factory()->create();
        $this->subscribeToPlan($user, 'dealer');
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);
        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false, 'high_risk_marker' => true]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertDontSeeText('High Risk');
    }

    public function test_high_risk_data_is_shown_to_a_verified_dealer_subscriber_on_a_check_report(): void
    {
        $dealer = $this->verifiedDealer();
        $check = VehicleCheck::factory()->create([
            'user_id' => $dealer->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);
        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false, 'high_risk_marker' => true]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test.']);

        $this->actingAs($dealer);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('High Risk')
            ->assertSeeText('High risk marker found');

        $pdfHtml = view('pdf.check-report', ['check' => $check->fresh()])->render();
        $this->assertStringContainsString('High Risk', $pdfHtml);
        $this->assertStringContainsString('High risk marker found', $pdfHtml);
    }

    public function test_a_verified_dealer_subscriber_sees_no_high_risk_marker_found_when_genuinely_clean(): void
    {
        $dealer = $this->verifiedDealer();
        $check = VehicleCheck::factory()->create([
            'user_id' => $dealer->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);
        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false, 'high_risk_marker' => false]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_PLUS, 'headline_summary' => 'Test.']);

        $this->actingAs($dealer);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('No high risk marker found');
    }
}
