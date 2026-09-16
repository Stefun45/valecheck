<?php

namespace App\Services\Admin;

use App\Models\AdminMetricReset;
use App\Models\AiUsage;
use App\Models\Commission;
use App\Models\Creator;
use App\Models\FreeLookupLog;
use App\Models\ListingImport;
use App\Models\Payment;
use App\Models\ProductPrice;
use App\Models\ProviderEndpointCost;
use App\Models\ProviderLookupLog;
use App\Models\Referral;
use App\Models\User;
use App\Models\VehicleCheck;
use Laravel\Cashier\Subscription;

/**
 * Read-only rollup of the numbers that matter for deciding whether
 * ValeCheck is actually contributing money after variable costs — not a
 * full accounting system, just enough visibility to run the business.
 */
class AdminMetricsService
{
    /**
     * Every endpoint a report type could possibly call, at the point
     * VehicleCheckPipeline dispatches its jobs — used only for the
     * forward-looking "worst case at today's rates" figure below, deliberately
     * NOT for the historical spend figures above, which must stay tied to
     * what a call actually cost when it happened. The two mutually
     * exclusive valuation endpoints (clean vs. written-off) are handled
     * separately in maxCostFor(), not listed here.
     *
     * @var array<string, list<string>>
     */
    private const MAX_COST_ENDPOINTS = [
        VehicleCheck::TYPE_CHECK => [
            'experian/autocheck/v3',
            'oneauto/mothistoryandtaxstatus/v2',
        ],
        VehicleCheck::TYPE_PLUS => [
            'experian/autocheck/v3',
            'oneauto/mothistoryandtaxstatus/v2',
            'vehicleimagery/imagesearchfromvrm',
            'vehicleimagery/imagefromid',
            'carguide/salvagecheck/v2',
            'oneauto/vehicletaxfromvrm/v2',
        ],
        VehicleCheck::TYPE_REBUILD => [
            'experian/autocheck/v3',
            'oneauto/mothistoryandtaxstatus/v2',
            'vehicleimagery/imagesearchfromvrm',
            'vehicleimagery/imagefromid',
        ],
    ];

    public function compute(): array
    {
        // The public /sample-report demo check is excluded from every
        // business figure below — it's not a real customer, so it must
        // never inflate completed-check counts, revenue, or cost averages.
        $completedCheck = VehicleCheck::where('status', VehicleCheck::STATUS_COMPLETED)->where('type', VehicleCheck::TYPE_CHECK)->where('is_sample', false)->count();
        $completedPlus = VehicleCheck::where('status', VehicleCheck::STATUS_COMPLETED)->where('type', VehicleCheck::TYPE_PLUS)->where('is_sample', false)->count();
        $completedRebuild = VehicleCheck::where('status', VehicleCheck::STATUS_COMPLETED)->where('type', VehicleCheck::TYPE_REBUILD)->where('is_sample', false)->count();
        $failedChecks = VehicleCheck::where('status', VehicleCheck::STATUS_FAILED)->where('is_sample', false)->count();

        // Lifetime figures — kept for the avg-cost-per-report metrics
        // below, which are meant to be genuine long-run averages, not
        // reset by the calendar. The dashboard's headline "Revenue" tiles
        // use the calendar-month figures further down instead.
        $revenue = (float) Payment::where('status', Payment::STATUS_PAID)->sum('gross');
        $paidPaymentsCount = Payment::where('status', Payment::STATUS_PAID)->count();

        // Each successful call's cost is whatever was actually configured
        // per-endpoint at the moment it was logged (snapshotted onto the
        // row by OneAutoClient — see ProviderEndpointCost), not today's
        // config re-applied to every historical call, so this figure is
        // accurate for any period and unaffected by later cost edits.
        $apiSpend = (float) ProviderLookupLog::where('status', ProviderLookupLog::STATUS_SUCCESS)->sum('cost_net');

        $aiSpend = (float) AiUsage::where('success', true)->get()
            ->sum(fn (AiUsage $usage) => (float) ($usage->actual_cost ?? $usage->estimated_cost ?? 0));

        $paymentProcessing = config('valecheck.payment_processing', ['percentage' => 0.015, 'fixed' => 0.20]);
        $paymentCost = ($revenue * $paymentProcessing['percentage']) + ($paidPaymentsCount * $paymentProcessing['fixed']);

        $totalCosts = $apiSpend + $aiSpend + $paymentCost;
        $contributionMargin = $revenue - $totalCosts;

        // Real total API spend actually logged against completed checks of
        // this type, divided by how many there are — reflects genuine
        // per-report cost (including any saving from a cached MOT/Tax
        // call) rather than an assumed fixed number of calls per report.
        $avgPaymentCost = $paidPaymentsCount > 0 ? $paymentCost / $paidPaymentsCount : 0;
        $apiSpendByType = fn (string $type) => (float) ProviderLookupLog::where('status', ProviderLookupLog::STATUS_SUCCESS)
            ->whereHas('vehicleCheck', fn ($query) => $query->where('type', $type)->where('status', VehicleCheck::STATUS_COMPLETED))
            ->sum('cost_net');
        $avgCostPerCheck = $completedCheck > 0 ? ($apiSpendByType(VehicleCheck::TYPE_CHECK) / $completedCheck) + $avgPaymentCost : 0;
        $avgCostPerPlus = $completedPlus > 0 ? ($apiSpendByType(VehicleCheck::TYPE_PLUS) / $completedPlus) + $avgPaymentCost : 0;
        $avgAiCostPerRebuild = $completedRebuild > 0 ? $aiSpend / $completedRebuild : 0;
        $avgCostPerRebuild = $completedRebuild > 0 ? ($apiSpendByType(VehicleCheck::TYPE_REBUILD) / $completedRebuild) + $avgAiCostPerRebuild + $avgPaymentCost : 0;

        // Worst-case API cost at TODAY's configured per-endpoint rates —
        // unlike everything above, this deliberately reads current
        // ProviderEndpointCost values live rather than a historical
        // snapshot, since the whole point is "what would a report cost
        // right now if every endpoint it could possibly call actually
        // fired," so it updates immediately when a cost is edited.
        $maxCostPerCheck = $this->maxCostFor(VehicleCheck::TYPE_CHECK);
        $maxCostPerPlus = $this->maxCostFor(VehicleCheck::TYPE_PLUS);
        $maxCostPerRebuild = $this->maxCostFor(VehicleCheck::TYPE_REBUILD);
        $sellingPrice = ProductPrice::pluck('gross', 'type');

        // The headline "Revenue" tiles — scoped to the current calendar
        // month so they reset automatically on the 1st, rather than
        // showing an ever-growing lifetime total. Costs and margin are
        // scoped to the same window so the margin math stays internally
        // consistent (this month's revenue against this month's costs).
        //
        // Also floored at the same manual reset point as "Revenue since
        // last reset" below — pressing that one button zeroes both, so
        // e.g. pre-launch test activity earlier in the month can be
        // cleared out and only "active" revenue from the reset onward
        // counts, without waiting for the 1st of next month.
        $resetPoint = AdminMetricReset::pointFor('revenue_today');
        $monthStart = $resetPoint->greaterThan(now()->startOfMonth()) ? $resetPoint : now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthlyPayments = Payment::where('status', Payment::STATUS_PAID)->whereBetween('created_at', [$monthStart, $monthEnd]);
        $monthlyRevenue = (float) (clone $monthlyPayments)->sum('gross');
        $monthlyRevenueExVat = (float) (clone $monthlyPayments)->sum('net');
        $monthlyPaidPaymentsCount = (clone $monthlyPayments)->count();

        $monthlyApiSpend = (float) ProviderLookupLog::where('status', ProviderLookupLog::STATUS_SUCCESS)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('cost_net');
        $monthlyAiSpend = (float) AiUsage::where('success', true)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->get()
            ->sum(fn (AiUsage $usage) => (float) ($usage->actual_cost ?? $usage->estimated_cost ?? 0));
        $monthlyPaymentCost = ($monthlyRevenue * $paymentProcessing['percentage']) + ($monthlyPaidPaymentsCount * $paymentProcessing['fixed']);
        $monthlyTotalCosts = $monthlyApiSpend + $monthlyAiSpend + $monthlyPaymentCost;
        $monthlyContributionMargin = $monthlyRevenue - $monthlyTotalCosts;

        // A separate, manually-resettable running total — not tied to the
        // calendar at all, so the site owner can zero it whenever they
        // choose (e.g. after checking figures) rather than waiting for
        // midnight or month-end.
        $revenueSinceReset = (float) Payment::where('status', Payment::STATUS_PAID)
            ->where('created_at', '>=', $resetPoint)
            ->sum('gross');

        return [
            'users_count' => User::count(),
            'checks_completed' => $completedCheck,
            'plus_completed' => $completedPlus,
            'rebuild_completed' => $completedRebuild,
            'checks_failed' => $failedChecks,
            'active_subscriptions' => Subscription::where('stripe_status', 'active')->count(),

            'revenue' => $monthlyRevenue,
            'revenue_ex_vat' => $monthlyRevenueExVat,
            'revenue_since_reset' => $revenueSinceReset,
            'free_lookups_count' => FreeLookupLog::count(),
            'api_spend' => $apiSpend,
            'ai_spend' => $aiSpend,
            'payment_processing_cost' => $monthlyPaymentCost,
            'total_costs' => $monthlyTotalCosts,
            'contribution_margin' => $monthlyContributionMargin,
            'contribution_margin_pct' => $monthlyRevenue > 0 ? ($monthlyContributionMargin / $monthlyRevenue) * 100 : 0,
            'avg_cost_per_check' => $avgCostPerCheck,
            'avg_cost_per_plus' => $avgCostPerPlus,
            'avg_cost_per_rebuild' => $avgCostPerRebuild,

            'max_cost_per_check' => $maxCostPerCheck,
            'max_cost_per_plus' => $maxCostPerPlus,
            'max_cost_per_rebuild' => $maxCostPerRebuild,
            'max_margin_per_check' => (float) ($sellingPrice[VehicleCheck::TYPE_CHECK] ?? 0) - $maxCostPerCheck,
            'max_margin_per_plus' => (float) ($sellingPrice[VehicleCheck::TYPE_PLUS] ?? 0) - $maxCostPerPlus,
            'max_margin_per_rebuild' => (float) ($sellingPrice[VehicleCheck::TYPE_REBUILD] ?? 0) - $maxCostPerRebuild,

            'failed_ai_calls' => AiUsage::where('success', false)->count(),
            'failed_checks' => $failedChecks,

            'affiliates_count' => Creator::count(),
            'referrals_count' => Referral::count(),
            'commissions_total' => (float) Commission::sum('amount'),

            'listing_import' => $this->listingImportStats(),
        ];
    }

    private function maxCostFor(string $type): float
    {
        $costs = ProviderEndpointCost::pluck('cost_net', 'endpoint');

        $total = collect(self::MAX_COST_ENDPOINTS[$type] ?? [])
            ->sum(fn (string $endpoint) => (float) ($costs[$endpoint] ?? 0));

        // The valuation call is one or the other, never both — take
        // whichever currently costs more so this stays a genuine ceiling.
        if (in_array($type, [VehicleCheck::TYPE_PLUS, VehicleCheck::TYPE_REBUILD], true)) {
            $total += max(
                (float) ($costs['ukvehicledata/valuationfromvrm/v2'] ?? 0),
                (float) ($costs['salvageguide/bidpredictionfromvrm'] ?? 0),
            );
        }

        return $total;
    }

    private function listingImportStats(): array
    {
        $attempts = ListingImport::whereIn('status', ListingImport::TERMINAL_STATUSES)->get();

        $byProvider = $attempts->groupBy('provider')->map(fn ($group) => [
            'total' => $group->count(),
            'success' => $group->whereIn('status', [ListingImport::STATUS_SUCCESS, ListingImport::STATUS_PARTIAL])->count(),
        ])->all();

        return [
            'total_attempts' => $attempts->count(),
            'successful' => $attempts->where('status', ListingImport::STATUS_SUCCESS)->count(),
            'partial' => $attempts->where('status', ListingImport::STATUS_PARTIAL)->count(),
            'failed' => $attempts->where('status', ListingImport::STATUS_FAILED)->count(),
            'blocked' => $attempts->where('status', ListingImport::STATUS_BLOCKED)->count(),
            'avg_duration_ms' => $attempts->count() > 0 ? (int) $attempts->avg('duration_ms') : 0,
            'avg_images_found' => $attempts->count() > 0 ? round($attempts->avg('image_count_found'), 1) : 0,
            'by_provider' => $byProvider,
        ];
    }
}
