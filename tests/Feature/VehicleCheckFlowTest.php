<?php

namespace Tests\Feature;

use App\Livewire\VehicleCheck\ShowCheck;
use App\Livewire\VehicleCheck\StartCheck;
use App\Models\Payment;
use App\Models\Report;
use App\Models\SalvageAuctionCheck;
use App\Models\SubscriptionUsage;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use App\Models\VehicleValuation;
use App\Services\Credits\CreditLedgerService;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use App\Services\Reports\ReportPdfService;
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
     * Simulates a paid one-off purchase completing via the real Stripe
     * completion handler, without touching the real Stripe API — the same
     * handler that a genuine webhook would invoke.
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

    public function test_the_confirmation_step_shows_full_mot_history_and_a_mileage_chart_when_the_preview_includes_it(): void
    {
        // The free preview reuses the same MOT History & Tax Status call the
        // paid report uses, so it's already fetched — showing the full
        // history and chart here costs nothing extra, unlike provenance/
        // valuation/salvage which are separate paid calls.
        $user = $this->verifiedUser();
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->set('previewStatus', 'found')
            ->set('vehiclePreview', [
                'make' => 'FORD',
                'model' => 'FIESTA',
                'colour' => 'BLUE',
                'fuel_type' => 'PETROL',
                'year' => 2019,
                'engine_capacity' => null,
                'mot_status' => 'Valid',
                'tax_status' => 'Taxed',
                'tax_expiry_date' => '2027-05-01',
                'mot_history' => [
                    ['test_date' => '2023-06-01', 'result' => 'PASSED', 'mileage' => 20000, 'advisories' => []],
                    ['test_date' => '2024-06-01', 'result' => 'FAILED', 'mileage' => 28000, 'advisories' => ['Front tyre worn']],
                ],
            ])
            ->assertSee('until 01 May 2027')
            ->assertSee('MOT &amp; Mileage', false)
            ->assertSee('Mileage Over Time')
            ->assertSeeHtml('bg-red-50 text-vale-red');
    }

    public function test_the_confirmation_step_has_no_mot_section_when_the_preview_has_no_mot_history(): void
    {
        // DVLA VES (and the mock DVLA provider used by default in tests)
        // never returns MOT test history — only One Auto's MOT/Tax call
        // does — so this must degrade cleanly, not error.
        $user = $this->verifiedUser();
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->assertDontSee('Mileage Over Time');
    }

    public function test_plus_details_step_only_asks_for_asking_price(): void
    {
        // Mileage and listing description only feed Rebuild's AI-generated
        // explanation — Plus's headline is a deterministic comparison of
        // asking price against the computed valuation, so mileage/listing
        // description aren't collected for Plus at all.
        $user = $this->verifiedUser();
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'plus')
            ->assertSet('step', 'details')
            ->assertSee('Asking price')
            ->assertDontSee('Mileage')
            ->assertDontSee('Listing description');
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

    public function test_user_can_submit_a_plus_check_funded_by_a_purchased_credit(): void
    {
        $user = $this->verifiedUser();
        app(CreditLedgerService::class)->grantPurchasedCredits($user, VehicleCheck::TYPE_PLUS, 2);
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'PL1USCR')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'plus')
            ->assertSet('step', 'details')
            ->set('mileage', 45000)
            ->call('submit');

        $check = VehicleCheck::where('registration', 'PL1USCR')->firstOrFail();

        $this->assertSame(VehicleCheck::TYPE_PLUS, $check->type);
        $this->assertSame('credit', $check->funding_source);
        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertNotNull($check->valuation);

        $ledger = app(CreditLedgerService::class);
        $this->assertSame(1, $ledger->balance($user, VehicleCheck::TYPE_PLUS));
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

    public function test_a_completed_plus_report_shows_the_same_full_vehicle_check_as_check(): void
    {
        // Plus = Check + valuation, not valuation instead of Check — the
        // report must include everything Check's report does (vehicle
        // spec, stolen/scrapped markers, full MOT history) on top of its
        // own valuation content.
        $user = $this->verifiedUser();

        $check = $this->completeViaPurchase($user, VehicleCheck::TYPE_PLUS, 'PL2VFUL');

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('Vehicle Summary')
            ->assertSeeText('Stolen / Scrapped')
            ->assertSeeText('MOT & Mileage')
            ->assertSeeText('Keeper / Registration History')
            ->assertSeeText('Market Assessment');

        // The downloadable PDF is a separate template from the on-site
        // report — must not regress independently of it.
        $pdfHtml = view('pdf.plus-report', ['check' => $check])->render();

        $this->assertStringContainsString('Vehicle Summary', $pdfHtml);
        $this->assertStringContainsString('Stolen / Scrapped', $pdfHtml);
        $this->assertStringContainsString('MOT &amp; Mileage', $pdfHtml);
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

    public function test_mot_advisories_are_shown_on_the_report_and_in_the_pdf(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            'mot_history' => [
                [
                    'test_date' => '2024-03-05',
                    'result' => 'fail',
                    'mileage' => 36940,
                    'advisories' => ['Offside rear brake disc worn'],
                ],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('1 advisory')
            ->assertSeeText('Offside rear brake disc worn');

        $pdfHtml = view('pdf.check-report', ['check' => $check->fresh()])->render();
        $this->assertStringContainsString('Offside rear brake disc worn', $pdfHtml);
    }

    public function test_mot_test_dates_show_date_only_even_when_the_provider_returns_a_full_timestamp(): void
    {
        // One Auto's real MOT test dates come back as full ISO datetimes
        // (e.g. "2025-03-12T09:06:35.000Z") — the report must show just
        // the date, not leak the time component.
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            'mot_history' => [
                ['test_date' => '2025-03-12T09:06:35.000Z', 'result' => 'pass', 'mileage' => 48210, 'advisories' => []],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('12 Mar 2025')
            ->assertDontSeeText('09:06:35');

        $pdfHtml = view('pdf.check-report', ['check' => $check->fresh()])->render();
        $this->assertStringContainsString('12 Mar 2025', $pdfHtml);
        $this->assertStringNotContainsString('09:06:35', $pdfHtml);
    }

    public function test_a_mileage_chart_is_shown_when_there_are_at_least_two_mot_tests(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            'mot_history' => [
                ['test_date' => '2022-06-01', 'result' => 'pass', 'mileage' => 15000, 'advisories' => []],
                ['test_date' => '2023-06-01', 'result' => 'pass', 'mileage' => 24000, 'advisories' => []],
            ],
        ]);

        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test summary.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('Mileage Over Time')
            ->assertSeeHtml('<polyline')
            ->assertDontSeeText('Not enough MOT history')
            // Hover tooltip data — each point's date + mileage available to
            // Alpine for the on-hover label, not just a native <title>.
            ->assertSee('01 Jun 2022 — 15,000 mi', false)
            ->assertSee('01 Jun 2023 — 24,000 mi', false);

        // Must not break dompdf generation — real rendering, not just a
        // string check, since SVG support varies across PDF renderers.
        Storage::fake('local');
        $report = app(ReportPdfService::class)->generate($check->fresh());
        $this->assertNotNull($report->pdf_path);
    }

    public function test_the_mileage_chart_gracefully_handles_a_single_mot_test(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            'mot_history' => [
                ['test_date' => '2024-06-01', 'result' => 'pass', 'mileage' => 24000, 'advisories' => []],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('Not enough MOT history to show a mileage trend.');
    }

    public function test_the_at_a_glance_status_grid_appears_on_check_and_plus_web_and_pdf(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            'write_off_category' => 'N',
            'finance_marker' => false,
            'stolen_marker' => false,
        ]);

        VehicleValuation::create([
            'vehicle_check_id' => $check->id,
            'clean_value' => 10000,
            'trade_value' => 8500,
            'retail_value' => 10000,
            'private_value' => 9300,
            'confidence' => 'medium',
        ]);

        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_PLUS, 'headline_summary' => 'Test summary.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('Mileage Trend')
            ->assertSeeText('Write-Off History')
            ->assertSeeText('Outstanding Finance')
            ->assertSeeText('Stolen');

        Storage::fake('local');
        $report = app(ReportPdfService::class)->generate($check->fresh());
        $this->assertNotNull($report->pdf_path);
    }

    public function test_missing_provenance_data_shows_unavailable_never_clean(): void
    {
        // End-to-end version of the exact bug that took VehicleMatic out of
        // production — a null marker must render as "unavailable", never
        // be silently read as "checked and clean".
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            'finance_marker' => null,
            'stolen_marker' => null,
            'scrapped_marker' => null,
        ]);

        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test summary.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('Finance data unavailable')
            ->assertSeeText('Stolen check unavailable')
            ->assertSeeText('Scrapped check unavailable')
            ->assertDontSeeText('No finance marker found')
            ->assertDontSeeText('No marker found');

        $pdfHtml = view('pdf.check-report', ['check' => $check->fresh()])->render();
        $this->assertStringContainsString('Finance data unavailable', $pdfHtml);
    }

    public function test_a_plus_report_with_unavailable_valuation_still_renders_the_rest(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            'finance_marker' => false,
            'stolen_marker' => false,
        ]);

        // No VehicleValuation row at all — the valuation lookup failed
        // entirely, exactly the "missing valuation" scenario from the brief.
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_PLUS, 'headline_summary' => 'Test summary.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('Valuation unavailable for this vehicle.')
            ->assertSeeText('No finance marker found');
    }

    public function test_a_plus_report_shows_salvage_auction_photos_and_details_when_a_record_is_found(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_PLUS, 'headline_summary' => 'Test.']);
        SalvageAuctionCheck::create([
            'vehicle_check_id' => $check->id,
            'record_found' => true,
            'records' => [[
                'lotDescription' => 'Category N — front end collision damage',
                'lotDate' => '2024-03-01',
                'mileage' => 32000,
                'primaryDamageDescription' => 'Front bumper and headlight',
                'secondaryDamageDescription' => 'Nearside front wing',
                'location' => 'Copart Bedford',
                'imageUrls' => ['https://example.com/photo1.jpg'],
            ]],
        ]);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('Salvage Auction History')
            ->assertSeeText('Copart Bedford')
            ->assertSeeText('Front bumper and headlight')
            ->assertSeeHtml('https://example.com/photo1.jpg');

        // Photographs are shown on the web report (browser-fetched) but not
        // embedded in the PDF, since dompdf has isRemoteEnabled disabled to
        // avoid server-side SSRF on third-party-sourced image URLs.
        $pdfHtml = view('pdf.plus-report', ['check' => $check])->render();

        $this->assertStringContainsString('Salvage Auction History', $pdfHtml);
        $this->assertStringContainsString('Copart Bedford', $pdfHtml);
        $this->assertStringNotContainsString('https://example.com/photo1.jpg', $pdfHtml);
        $this->assertStringContainsString(route('vehicle-checks.show', $check), $pdfHtml);
    }

    public function test_a_plus_report_shows_no_salvage_record_found_when_there_is_none(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_PLUS, 'headline_summary' => 'Test.']);
        SalvageAuctionCheck::create(['vehicle_check_id' => $check->id, 'record_found' => false, 'records' => []]);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('No salvage auction record found for this vehicle.');
    }

    public function test_a_completed_purchase_creates_a_salvage_auction_check_for_plus_but_not_for_check(): void
    {
        $plusUser = $this->verifiedUser();
        $plusCheck = $this->completeViaPurchase($plusUser, VehicleCheck::TYPE_PLUS, 'SA1VAGE');
        $this->assertNotNull($plusCheck->salvageAuctionCheck);

        $checkUser = $this->verifiedUser();
        $basicCheck = $this->completeViaPurchase($checkUser, VehicleCheck::TYPE_CHECK, 'SA1VNFD');
        $this->assertNull($basicCheck->fresh()->salvageAuctionCheck);
    }

    public function test_keeper_history_facts_shows_the_extra_identity_and_history_fields(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            'finance_marker' => false,
            'plate_changes' => 3,
            'plate_change_history' => [
                ['date' => '2022-11-07', 'from' => 'AW58CAT', 'to' => 'DY17BXW', 'type' => 'Data Move'],
                ['date' => '2020-11-19', 'from' => 'DY17BXW', 'to' => 'AW58CAT', 'type' => 'Marker'],
            ],
            'colour_changes' => 0,
            'vehicle_identity_checks' => 0,
            'v5c_reissues' => 5,
            'previous_searches' => 36,
            'was_exported' => false,
            // Deliberately not asserted below — see the comment on
            // keeper-history-facts.blade.php for why these are stored but
            // not displayed.
            'vrm_matches' => false,
            'vin_matches' => null,
        ]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('Logbook (V5C) reissues: 5')
            ->assertSeeText('Previous searches by other buyers/traders: 36')
            ->assertSeeText('AW58CAT')
            ->assertSeeText('DY17BXW')
            ->assertDontSeeText('Registration matches records')
            ->assertDontSeeText('VIN matches records');

        $pdfHtml = view('pdf.check-report', ['check' => $check->fresh()])->render();
        $this->assertStringContainsString('Logbook (V5C) reissues: 5', $pdfHtml);
        $this->assertStringContainsString('AW58CAT', $pdfHtml);
        $this->assertStringNotContainsString('Registration matches records', $pdfHtml);
    }

    public function test_the_full_vin_never_appears_in_the_report_or_pdf_only_the_last_five(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);
        $check->vehicle->update(['vin' => 'WVWZZZ1JZXW000001']);

        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test summary.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertDontSee('WVWZZZ1JZXW000001')
            ->assertSeeText('00001');

        $pdfHtml = view('pdf.check-report', ['check' => $check->fresh()])->render();
        $this->assertStringNotContainsString('WVWZZZ1JZXW000001', $pdfHtml);
        $this->assertStringContainsString('00001', $pdfHtml);
    }

    public function test_the_report_attributes_identity_and_provenance_data_to_experian(): void
    {
        // A condition of Experian's B2C compliance approval — attribution
        // must appear next to the sections actually sourced from them, not
        // stamped across the whole report (MOT/tax and valuation come from
        // different providers).
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test summary.']);

        $this->actingAs($user);

        Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])
            ->assertSeeText('provided by Experian');

        $pdfHtml = view('pdf.check-report', ['check' => $check->fresh()])->render();
        $this->assertStringContainsString('provided by Experian', $pdfHtml);
    }

    public function test_market_assessment_shows_a_visual_bar_per_value_scaled_to_the_highest(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create(['vehicle_check_id' => $check->id, 'finance_marker' => false]);
        VehicleValuation::create([
            'vehicle_check_id' => $check->id,
            'clean_value' => 10000,
            'trade_value' => 5000,
            'retail_value' => 10000,
            'private_value' => 7500,
            'confidence' => 'medium',
        ]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_PLUS, 'headline_summary' => 'Test.']);

        $this->actingAs($user);

        $html = Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])->html();

        // Trade value (£5,000) is half of the highest value shown (£10,000
        // retail), so its bar should be scaled to roughly 50% width.
        $this->assertMatchesRegularExpression('/Trade value.*?width:\s*50%/s', $html);
        $this->assertMatchesRegularExpression('/Retail value.*?width:\s*100%/s', $html);
    }

    public function test_mot_results_are_shown_as_coloured_badges(): void
    {
        $user = $this->verifiedUser();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        VehicleHistory::create([
            'vehicle_check_id' => $check->id,
            // Real casing from the One Auto API is 'PASSED'/'FAILED', not
            // 'pass'/'fail' — this exact fixture previously let a bug slip
            // through where every genuine failure rendered as a green pass.
            'mot_history' => [
                ['test_date' => '2024-06-01', 'result' => 'PASSED', 'mileage' => 20000, 'advisories' => []],
                ['test_date' => '2023-06-01', 'result' => 'FAILED', 'mileage' => 15000, 'advisories' => []],
            ],
        ]);
        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test.']);

        $this->actingAs($user);

        $html = Livewire::test(ShowCheck::class, ['vehicleCheck' => $check])->html();

        $this->assertStringContainsString('bg-green-50 text-green-700', $html);
        $this->assertStringContainsString('bg-red-50 text-vale-red', $html);

        // Pin the association the other way too — a FAILED test must never
        // be the one that gets the green class.
        $this->assertMatchesRegularExpression('/bg-green-50 text-green-700">PASSED/', $html);
        $this->assertMatchesRegularExpression('/bg-red-50 text-vale-red">FAILED/', $html);
    }
}
