<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingVsEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private function completeRebuildViaPurchase(string $registration, ?string $listingDescription): VehicleCheck
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['registration' => $registration]);

        $check = VehicleCheck::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_REBUILD,
            'status' => VehicleCheck::STATUS_PENDING,
            'funding_source' => 'purchase',
            'registration' => $registration,
            'asking_price' => 7500,
            'listing_description' => $listingDescription,
        ]);

        // AnalyseImages only calls the AI provider when there's at least one
        // photo — without one, this test's fake Anthropic response sequence
        // (damage findings, explanation, [comparison]) would be consumed out
        // of order.
        $check->images()->create([
            'disk' => 'local',
            'path' => UploadedFile::fake()->image('front.jpg')->store('vehicle-check-uploads', 'local'),
            'position' => 0,
            'source' => 'uploaded',
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_REBUILD,
            'description' => 'ValeCheck Rebuild',
            'gross' => 14.99,
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

    public function test_listing_vs_evidence_is_generated_when_a_listing_description_is_present(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'content' => [[
                        'type' => 'tool_use',
                        'name' => 'report_damage_findings',
                        'input' => [
                            'summary' => 'Visible front-end damage.',
                            'confidence' => 'medium',
                            'findings' => [
                                ['component' => 'front_bumper', 'condition' => 'damaged', 'severity' => 'medium', 'recommended_action' => 'repair', 'confidence' => 0.8, 'explanation' => 'Cracked bumper visible.'],
                            ],
                        ],
                    ]],
                    'usage' => ['input_tokens' => 800, 'output_tokens' => 200],
                ])
                ->push([
                    'content' => [['type' => 'text', 'text' => 'Front-end damage consistent with a collision.']],
                    'usage' => ['input_tokens' => 400, 'output_tokens' => 100],
                ])
                ->push([
                    'content' => [[
                        'type' => 'tool_use',
                        'name' => 'report_listing_comparison',
                        'input' => [
                            'comparisons' => [
                                [
                                    'claim' => 'Runs and drives, minor cosmetic damage only.',
                                    'valecheck_observation' => 'Photographs show a cracked front bumper consistent with a collision, not merely cosmetic.',
                                    'verdict' => 'contradicted',
                                    'confidence' => 'medium',
                                ],
                                [
                                    'claim' => 'No mechanical issues.',
                                    'valecheck_observation' => 'Photographs cannot verify mechanical condition.',
                                    'verdict' => 'inconclusive',
                                    'confidence' => 'low',
                                ],
                            ],
                        ],
                    ]],
                    'usage' => ['input_tokens' => 300, 'output_tokens' => 150],
                ]),
        ]);

        $check = $this->completeRebuildViaPurchase('XY99ZZZ', 'Runs and drives, minor cosmetic damage only. No mechanical issues.');

        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);

        $report = $check->report;
        $this->assertNotNull($report->listing_vs_evidence);
        $this->assertCount(2, $report->listing_vs_evidence);
        $this->assertSame('contradicted', $report->listing_vs_evidence[0]['verdict']);

        // An inconclusive finding must never be phrased as a stated fact.
        $inconclusive = collect($report->listing_vs_evidence)->firstWhere('verdict', 'inconclusive');
        $this->assertStringContainsString('cannot', strtolower($inconclusive['observation']));
    }

    public function test_listing_vs_evidence_is_skipped_when_there_is_no_listing_description(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'content' => [[
                        'type' => 'tool_use',
                        'name' => 'report_damage_findings',
                        'input' => ['summary' => 'No visible damage.', 'confidence' => 'high', 'findings' => []],
                    ]],
                    'usage' => ['input_tokens' => 500, 'output_tokens' => 100],
                ])
                ->push([
                    'content' => [['type' => 'text', 'text' => 'Clean example.']],
                    'usage' => ['input_tokens' => 300, 'output_tokens' => 80],
                ]),
        ]);

        $check = $this->completeRebuildViaPurchase('NO1DESC', null);

        $this->assertSame(VehicleCheck::STATUS_COMPLETED, $check->status);
        $this->assertNull($check->report->listing_vs_evidence);
    }
}
