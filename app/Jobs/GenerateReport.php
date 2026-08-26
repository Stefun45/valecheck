<?php

namespace App\Jobs;

use App\DataTransferObjects\AnalysisData;
use App\Mail\ReportReadyEmail;
use App\Models\AiUsage;
use App\Models\Report;
use App\Models\VehicleCheck;
use App\Services\Ai\AiProvider;
use App\Services\Reports\ReportPdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GenerateReport implements ShouldQueue
{
    use Queueable;

    private const GENERIC_THINGS_TO_CHECK = [
        'Verify the current MOT and full service history in person.',
        'Confirm the VIN plate and number plate match the registration document.',
        'Test drive the vehicle to check for vibration, pulling or unusual noises.',
        'Check for uneven tyre wear, which can indicate suspension or alignment issues.',
    ];

    public int $tries = 3;

    public function __construct(public int $vehicleCheckId) {}

    public function handle(AiProvider $provider, ReportPdfService $pdfService): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $check->update(['stage' => 'generating_report']);

        $listingGaps = $this->buildListingGaps($check);
        $risks = $this->buildRisks($check);
        $listingVsEvidence = null;

        if ($check->needsDamageAnalysis()) {
            $headline = $this->generateRebuildHeadline($check, $provider);
            $thingsToCheck = self::GENERIC_THINGS_TO_CHECK;
            $listingVsEvidence = $this->buildListingVsEvidence($check, $provider);
        } elseif ($check->needsValuation()) {
            $headline = $this->generatePlusHeadline($check);
            $thingsToCheck = self::GENERIC_THINGS_TO_CHECK;
        } else {
            $headline = $this->generateCheckHeadline($check);
            $thingsToCheck = [];
        }

        Report::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'type' => $check->type,
                'headline_summary' => $headline,
                'listing_gaps' => $listingGaps,
                'risks' => $risks,
                'things_to_check' => $thingsToCheck,
                'listing_vs_evidence' => $listingVsEvidence,
                'generated_at' => now(),
            ]
        );

        $check->update([
            'status' => VehicleCheck::STATUS_COMPLETED,
            'stage' => null,
            'completed_at' => now(),
            'expires_at' => now()->addDays((int) config('valecheck.reports.retention_days')),
        ]);

        // Explicit, not a side effect of building the email's attachment —
        // the PDF is generated and stored (to S3 in production) the moment
        // the report completes, regardless of whether the email succeeds or
        // anyone ever clicks "Download PDF" on the site.
        $this->generateReportPdf($check, $pdfService);
        $this->sendReportReadyEmail($check);
    }

    /**
     * A failed generation must never undo or retry an already-completed
     * report — it's logged and swallowed. ReportReadyEmail's own attachment
     * builder calls generate() again as a fallback, so a transient failure
     * here still gets a second chance when the email is built.
     */
    private function generateReportPdf(VehicleCheck $check, ReportPdfService $pdfService): void
    {
        try {
            $pdfService->generate($check);
        } catch (Throwable $e) {
            Log::error("Failed to generate report PDF for VehicleCheck #{$check->id}: {$e->getMessage()}");
        }
    }

    /**
     * A failed send (Postmark hiccup, etc.) must never undo or retry an
     * already-completed report — it's logged and swallowed.
     */
    private function sendReportReadyEmail(VehicleCheck $check): void
    {
        try {
            Mail::to($check->user)->send(new ReportReadyEmail($check));
        } catch (Throwable $e) {
            Log::error("Failed to send report-ready email for VehicleCheck #{$check->id}: {$e->getMessage()}");
        }
    }

    private function generateCheckHeadline(VehicleCheck $check): string
    {
        $history = $check->history;
        $vehicle = $check->vehicle;

        if (! $history) {
            return 'History could not be retrieved for this vehicle.';
        }

        $parts = [];
        $parts[] = $history->isWrittenOff()
            ? "This vehicle has a recorded Category {$history->write_off_category} write-off."
            : 'No insurance write-off history is recorded for this vehicle.';

        $parts[] = $history->finance_marker
            ? 'A finance marker was found — outstanding finance should be settled before any sale completes.'
            : 'No outstanding finance marker was found.';

        $parts[] = $history->previous_keepers
            ? "The vehicle has had {$history->previous_keepers} previous keeper(s)."
            : 'Keeper history is not available.';

        return implode(' ', $parts)." Vehicle: {$vehicle->description()}.";
    }

    /**
     * ValeCheck Plus adds a market-value position to the history assessment —
     * deterministic, not AI-generated, since it's just comparing numbers
     * already calculated by MarketValuationProvider / the asking price supplied.
     */
    private function generatePlusHeadline(VehicleCheck $check): string
    {
        $parts = [$this->generateCheckHeadline($check)];

        $valuation = $check->valuation;
        $askingPrice = $check->asking_price ? (float) $check->asking_price : null;

        if ($valuation?->clean_value) {
            $parts[] = 'Estimated retail value is around £'.number_format((float) $valuation->clean_value, 0).'.';

            if ($askingPrice) {
                $diffPct = (($askingPrice - (float) $valuation->clean_value) / (float) $valuation->clean_value) * 100;

                $parts[] = match (true) {
                    $diffPct > 10 => 'The asking price is notably above the estimated retail value.',
                    $diffPct < -10 => 'The asking price is notably below the estimated retail value — worth double-checking why.',
                    default => 'The asking price is broadly in line with the estimated retail value.',
                };
            }
        }

        return implode(' ', $parts);
    }

    private function generateRebuildHeadline(VehicleCheck $check, AiProvider $provider): string
    {
        $vehicleData = $check->toVehicleData();

        $data = new AnalysisData(
            vehicle: $vehicleData,
            history: $check->history?->only([
                'write_off_category', 'finance_marker', 'stolen_marker', 'imported', 'mileage_anomaly', 'previous_keepers',
            ]) ?? [],
            valuation: $check->valuation?->only([
                'clean_value', 'salvage_adjusted_value', 'write_off_category_applied', 'discount_applied', 'confidence',
            ]) ?? [],
            damageFindings: $this->damageFindingsArray($check),
            repairEstimate: $check->repairEstimate?->only(['low_estimate', 'expected_estimate', 'high_estimate']) ?? [],
            bidRecommendation: $check->bidRecommendation?->only([
                'maximum_acquisition_price', 'recommended_bid', 'absolute_maximum', 'deal_score', 'recommendation',
            ]) ?? [],
            listingContext: [
                'current_bid' => $check->current_bid,
                'asking_price' => $check->asking_price,
                'mileage' => $check->mileage,
                'listing_description' => $check->listing_description,
            ],
        );

        $startedAt = microtime(true);

        $text = $provider->generateReportExplanation($data);

        AiUsage::create([
            'vehicle_check_id' => $check->id,
            'provider' => config('valecheck.ai.provider'),
            'model' => config('valecheck.ai.model'),
            'stage' => 'explanation',
            'image_count' => 0,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'success' => true,
        ]);

        return $text;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function damageFindingsArray(VehicleCheck $check): array
    {
        $findings = $check->damageAnalysis?->findings->map->toData()->all() ?? [];

        return array_map(fn ($f) => [
            'component' => $f->component, 'condition' => $f->condition, 'severity' => $f->severity, 'explanation' => $f->explanation,
        ], $findings);
    }

    /**
     * "SELLER SAYS" vs "VALECHECK SEES" — only runs when there's actual
     * listing text to compare against (imported or manually typed), and
     * never blocks report generation if the AI call fails.
     */
    private function buildListingVsEvidence(VehicleCheck $check, AiProvider $provider): ?array
    {
        if (empty($check->listing_description)) {
            return null;
        }

        $startedAt = microtime(true);

        try {
            $result = $provider->compareListingToEvidence($check->listing_description, $this->damageFindingsArray($check));
        } catch (Throwable $e) {
            AiUsage::create([
                'vehicle_check_id' => $check->id,
                'provider' => config('valecheck.ai.provider'),
                'model' => config('valecheck.ai.model'),
                'stage' => 'listing_comparison',
                'image_count' => 0,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'success' => false,
                'error_message' => $e->getMessage(),
            ]);

            return null;
        }

        AiUsage::create([
            'vehicle_check_id' => $check->id,
            'provider' => config('valecheck.ai.provider'),
            'model' => config('valecheck.ai.model'),
            'stage' => 'listing_comparison',
            'image_count' => 0,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'success' => true,
        ]);

        return $result->toArray();
    }

    /**
     * "What the listing doesn't tell you" — every entry here is backed by an
     * actual data flag. Never invented.
     */
    private function buildListingGaps(VehicleCheck $check): array
    {
        $gaps = [];
        $history = $check->history;

        if ($history?->isWrittenOff()) {
            $gaps[] = "Previous Category {$history->write_off_category} recorded.";
        }

        if ($history?->finance_marker) {
            $gaps[] = 'Finance marker detected.';
        }

        if ($history?->mileage_anomaly) {
            $gaps[] = 'Mileage anomaly detected in the MOT history.';
        }

        if ($history?->imported) {
            $gaps[] = 'Vehicle previously imported.';
        }

        if ($history?->plate_changes) {
            $gaps[] = 'Number plate has been changed at least once.';
        }

        if ($check->needsDamageAnalysis()) {
            $damageAnalysis = $check->damageAnalysis;

            if ($damageAnalysis && $damageAnalysis->images_analysed === 0) {
                $gaps[] = 'No photographs were supplied for image analysis.';
            }

            foreach ($damageAnalysis?->findings ?? [] as $finding) {
                if ($finding->isDamaged() && $finding->severity === 'high') {
                    $gaps[] = "Possible {$finding->component} damage — inspect before buying.";
                }
            }
        }

        return $gaps;
    }

    private function buildRisks(VehicleCheck $check): array
    {
        $risks = [];
        $history = $check->history;

        if ($history?->stolen_marker) {
            $risks[] = 'Stolen marker recorded — do not proceed without further verification.';
        }

        if ($history?->write_off_category && in_array($history->write_off_category, ['A', 'B'], true)) {
            $risks[] = "Category {$history->write_off_category} vehicles are not normally roadworthy or legally resaleable as a repaired car.";
        }

        return $risks;
    }
}
