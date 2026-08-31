<?php

namespace Tests\Feature;

use App\Livewire\VehicleCheck\ShowCheck;
use App\Models\Report;
use App\Models\SubscriptionUsage;
use App\Models\User;
use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HighRiskDataVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function dealerSubscriber(): User
    {
        $user = User::factory()->create();

        SubscriptionUsage::create([
            'user_id' => $user->id,
            'plan' => 'dealer',
            'report_type' => 'plus',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'allowance' => 30,
            'used' => 0,
        ]);

        return $user;
    }

    public function test_user_is_dealer_subscriber_only_for_an_active_dealer_plan(): void
    {
        $dealer = $this->dealerSubscriber();
        $this->assertTrue($dealer->isDealerSubscriber());

        $trader = User::factory()->create();
        SubscriptionUsage::create([
            'user_id' => $trader->id,
            'plan' => 'trader',
            'report_type' => 'plus',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'allowance' => 5,
            'used' => 0,
        ]);
        $this->assertFalse($trader->isDealerSubscriber());

        $noSubscription = User::factory()->create();
        $this->assertFalse($noSubscription->isDealerSubscriber());
    }

    public function test_an_expired_dealer_subscription_does_not_grant_access(): void
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

        $this->assertFalse($user->fresh()->isDealerSubscriber());
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

    public function test_high_risk_data_is_shown_to_an_active_dealer_subscriber_on_a_check_report(): void
    {
        $dealer = $this->dealerSubscriber();
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

    public function test_a_dealer_subscriber_sees_no_high_risk_marker_found_when_genuinely_clean(): void
    {
        $dealer = $this->dealerSubscriber();
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
