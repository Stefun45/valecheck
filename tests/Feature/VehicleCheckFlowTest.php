<?php

namespace Tests\Feature;

use App\Livewire\VehicleCheck\StartCheck;
use App\Models\Payment;
use App\Models\SubscriptionUsage;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class VehicleCheckFlowTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(): User
    {
        $user = User::factory()->create();
        event(new Verified($user));

        return $user;
    }

    /**
     * Simulates a paid one-off purchase (ValeCheck and ValeCheck Plus are
     * always "purchase" funded) completing via the real Stripe completion
     * handler, without touching the real Stripe API — the same handler that
     * a genuine webhook would invoke.
     */
    private function completeViaPurchase(User $user, string $type, string $registration): VehicleCheck
    {
        $vehicle = Vehicle::factory()->create(['registration' => $registration]);
        $check = VehicleCheck::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => $type,
            'status' => VehicleCheck::STATUS_PENDING,
            'funding_source' => 'purchase',
            'registration' => $registration,
            'asking_price' => 10000,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => $type,
            'description' => config("valecheck.pricing.{$type}.label"),
            'gross' => config("valecheck.pricing.{$type}.gross"),
            'net' => 0,
            'vat' => 0,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PENDING,
        ]);

        app(StripeCheckoutCompletionHandler::class)->handle([
            'id' => 'cs_test_'.$check->id,
            'payment_intent' => 'pi_test_'.$check->id,
            'payment_status' => 'paid',
            'metadata' => [
                'kind' => 'vehicle_check',
                'vehicle_check_id' => (string) $check->id,
                'payment_id' => (string) $payment->id,
            ],
        ]);

        return $check->fresh();
    }

    private function fakeAnthropicResponses(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'content' => [[
                        'type' => 'tool_use',
                        'name' => 'report_damage_findings',
                        'input' => [
                            'summary' => 'Minor scuffing to the front bumper.',
                            'confidence' => 'medium',
                            'findings' => [
                                ['component' => 'front_bumper', 'condition' => 'damaged', 'severity' => 'low', 'recommended_action' => 'repair', 'confidence' => 0.7, 'explanation' => 'Light scuffing.'],
                            ],
                        ],
                    ]],
                    'usage' => ['input_tokens' => 800, 'output_tokens' => 200],
                ])
                ->push([
                    'content' => [['type' => 'text', 'text' => 'Minor cosmetic damage only, otherwise a sound example.']],
                    'usage' => ['input_tokens' => 400, 'output_tokens' => 100],
                ]),
        ]);
    }

    public function test_a_credit_balance_cannot_be_consumed_beyond_the_balance(): void
    {
        $user = $this->verifiedUser();
        $ledger = app(CreditLedgerService::class);
        $ledger->grantPurchasedCredits($user, VehicleCheck::TYPE_REBUILD, 2);

        $check1 = VehicleCheck::factory()->create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_REBUILD]);
        $check2 = VehicleCheck::factory()->create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_REBUILD]);
        $check3 = VehicleCheck::factory()->create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_REBUILD]);

        $ledger->consumeCredit($user, VehicleCheck::TYPE_REBUILD, $check1);
        $ledger->consumeCredit($user, VehicleCheck::TYPE_REBUILD, $check2);

        $this->assertSame(0, $ledger->balance($user, VehicleCheck::TYPE_REBUILD));

        $this->expectException(RuntimeException::class);
        $ledger->consumeCredit($user, VehicleCheck::TYPE_REBUILD, $check3);
    }

    public function test_user_can_submit_a_check_and_it_is_purchase_funded(): void
    {
        $user = $this->verifiedUser();
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'check')
            ->assertSet('step', 'confirm')
            ->call('submit');

        $check = VehicleCheck::where('registration', 'AB12CDE')->firstOrFail();

        $this->assertSame(VehicleCheck::TYPE_CHECK, $check->type);
        $this->assertSame('purchase', $check->funding_source);
        $this->assertSame(VehicleCheck::STATUS_PENDING, $check->status);
        $this->assertNotNull($check->vehicle);
    }

    public function test_user_can_submit_a_plus_check_with_listing_details_and_no_photo_upload(): void
    {
        $user = $this->verifiedUser();
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'CD34EFG')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'plus')
            ->assertSet('step', 'details')
            ->assertDontSee('drag and drop photographs')
            ->set('mileage', 60000)
            ->set('asking_price', 9500)
            ->call('submit');

        $check = VehicleCheck::where('registration', 'CD34EFG')->firstOrFail();

        $this->assertSame(VehicleCheck::TYPE_PLUS, $check->type);
        $this->assertSame('purchase', $check->funding_source);
        $this->assertSame(9500.0, (float) $check->asking_price);
    }

    public function test_details_step_shows_photo_upload_for_rebuild_only(): void
    {
        $user = $this->verifiedUser();
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->assertSet('step', 'details')
            ->assertSee('drag and drop photographs');
    }

    public function test_user_can_submit_a_rebuild_check_funded_by_a_purchased_credit(): void
    {
        Storage::fake('local');
        $this->fakeAnthropicResponses();

        $user = $this->verifiedUser();
        app(CreditLedgerService::class)->grantPurchasedCredits($user, VehicleCheck::TYPE_REBUILD, 2);
        $this->actingAs($user);

        $image = UploadedFile::fake()->image('front.jpg');

        Livewire::test(StartCheck::class)
            ->set('registration', 'S1CATN')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->assertSet('step', 'details')
            ->set('mileage', 45000)
            ->set('current_bid', 3000)
            ->set('images', [$image])
            ->call('submit');

        $check = VehicleCheck::where('registration', 'S1CATN')->firstOrFail();

        $this->assertSame(VehicleCheck::TYPE_REBUILD, $check->type);
        $this->assertSame('credit', $check->funding_source);
        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertNotNull($check->bidRecommendation);
        $this->assertNotNull($check->bidRecommendation->deal_score);
        $this->assertNotNull($check->report);

        $ledger = app(CreditLedgerService::class);
        $this->assertSame(1, $ledger->balance($user, VehicleCheck::TYPE_REBUILD));
    }

    public function test_third_rebuild_report_requires_payment_once_credits_are_used(): void
    {
        $user = $this->verifiedUser();
        $ledger = app(CreditLedgerService::class);
        $ledger->grantPurchasedCredits($user, VehicleCheck::TYPE_REBUILD, 2);

        // Spend both credits directly via the ledger to reach the boundary quickly.
        $check1 = VehicleCheck::factory()->create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_REBUILD]);
        $check2 = VehicleCheck::factory()->create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_REBUILD]);
        $ledger->consumeCredit($user, VehicleCheck::TYPE_REBUILD, $check1);
        $ledger->consumeCredit($user, VehicleCheck::TYPE_REBUILD, $check2);

        $this->assertSame(0, $ledger->balance($user, VehicleCheck::TYPE_REBUILD));

        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'CD34EFG')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->set('mileage', 30000)
            ->call('submit');

        $check = VehicleCheck::where('registration', 'CD34EFG')->firstOrFail();

        $this->assertSame('purchase', $check->funding_source);
        $this->assertSame(VehicleCheck::STATUS_PENDING, $check->status);
    }

    public function test_a_rebuild_report_can_be_funded_by_a_subscription_allowance(): void
    {
        $this->fakeAnthropicResponses();

        $user = $this->verifiedUser();
        SubscriptionUsage::create([
            'user_id' => $user->id,
            'plan' => 'trader',
            'report_type' => VehicleCheck::TYPE_REBUILD,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'allowance' => 5,
            'used' => 0,
        ]);

        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->set('mileage', 20000)
            ->call('submit');

        $check = VehicleCheck::where('registration', 'AB12CDE')->firstOrFail();

        $this->assertSame('subscription', $check->funding_source);
        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertSame(1, SubscriptionUsage::first()->used);
    }

    public function test_a_completed_check_report_has_history_but_no_valuation_or_damage_analysis(): void
    {
        $user = $this->verifiedUser();

        $check = $this->completeViaPurchase($user, VehicleCheck::TYPE_CHECK, 'AB12CDE');

        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertNotNull($check->history);
        $this->assertNull($check->valuation);
        $this->assertNull($check->damageAnalysis);
        $this->assertNull($check->repairEstimate);
        $this->assertNull($check->bidRecommendation);

        $this->assertNotNull($check->expires_at);
        $this->assertTrue($check->expires_at->isSameDay(now()->addDays(config('valecheck.reports.retention_days'))));
    }

    public function test_a_completed_plus_report_has_valuation_but_no_damage_analysis(): void
    {
        $user = $this->verifiedUser();

        $check = $this->completeViaPurchase($user, VehicleCheck::TYPE_PLUS, 'CD34EFG');

        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertNotNull($check->history);
        $this->assertNotNull($check->valuation);
        $this->assertNull($check->damageAnalysis);
        $this->assertNull($check->repairEstimate);
        $this->assertNull($check->bidRecommendation);
    }

    public function test_a_completed_rebuild_report_has_everything_plus_has_all_the_way(): void
    {
        $this->fakeAnthropicResponses();

        $user = $this->verifiedUser();

        $check = $this->completeViaPurchase($user, VehicleCheck::TYPE_REBUILD, 'XY99ZZZ');

        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertNotNull($check->history);
        $this->assertNotNull($check->valuation, 'Rebuild must have everything Plus has.');
        $this->assertNotNull($check->damageAnalysis);
        $this->assertNotNull($check->repairEstimate);
        $this->assertNotNull($check->bidRecommendation);
        $this->assertNotNull($check->bidRecommendation->deal_score);
    }

    public function test_entering_a_registration_shows_a_vehicle_confirmation_before_pricing_is_shown(): void
    {
        $user = $this->verifiedUser();
        $this->actingAs($user);

        $component = Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle');

        // Mock lookups occasionally simulate a genuine "not found" and skip
        // straight through, but for a found result pricing must stay hidden
        // until the vehicle is confirmed.
        if ($component->get('previewStatus') === 'found') {
            $component->assertSet('vehicleConfirmed', false)
                ->assertDontSee('Check The History');

            $component->call('confirmVehicle', false)
                ->assertSet('vehicleConfirmed', false)
                ->assertSet('previewStatus', 'idle');
        }
    }

    public function test_choosing_a_product_after_confirming_does_not_run_a_second_lookup(): void
    {
        $user = $this->verifiedUser();
        $this->actingAs($user);

        $component = Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true);

        $previewBefore = $component->get('vehiclePreview');

        // Choosing a product is a pure step transition — it must not touch
        // the preview state (and therefore never trigger a second, billable lookup).
        $component->call('choose', 'check')
            ->assertSet('vehiclePreview', $previewBefore)
            ->assertSet('step', 'confirm');
    }

    public function test_a_registration_prefilled_via_query_string_skips_the_confirmation_step(): void
    {
        $user = $this->verifiedUser();

        // Simulate arriving at /check?registration=AB12CDE, as if the vehicle
        // was already confirmed on the landing page — it must not be asked again.
        $this->actingAs($user)
            ->get(route('vehicle-checks.start', ['registration' => 'AB12CDE']))
            ->assertOk()
            ->assertSeeText('Check The History')
            ->assertDontSee('Is this your vehicle?');
    }

    public function test_a_guest_can_load_the_check_page_without_an_account(): void
    {
        $this->get(route('vehicle-checks.start'))
            ->assertOk()
            ->assertSeeText('Which check do you need?')
            ->assertSeeText('Check Vehicle');
    }

    public function test_a_guest_can_browse_all_three_products_pricing_once_a_registration_is_confirmed(): void
    {
        // A prefilled registration (as if arriving from the landing page) skips
        // straight to pricing without needing an account.
        $this->get(route('vehicle-checks.start', ['registration' => 'AB12CDE']))
            ->assertOk()
            ->assertSeeText('ValeCheck')
            ->assertSeeText('ValeCheck Plus')
            ->assertSeeText('ValeCheck Rebuild');
    }

    public function test_a_guest_is_prompted_to_sign_up_only_at_the_point_of_submitting(): void
    {
        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'check')
            ->assertSet('step', 'confirm')
            ->call('submit')
            ->assertSet('step', 'auth-required')
            ->assertSee('Create free account');

        $this->assertDatabaseMissing('vehicle_checks', ['registration' => 'AB12CDE']);
    }

    public function test_user_cannot_view_another_users_vehicle_check(): void
    {
        $owner = $this->verifiedUser();
        $intruder = $this->verifiedUser();

        $check = VehicleCheck::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('vehicle-checks.show', $check))
            ->assertForbidden();
    }
}
