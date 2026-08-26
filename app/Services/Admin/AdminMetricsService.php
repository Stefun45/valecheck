<?php

namespace App\Services\Admin;

use App\Models\AiUsage;
use App\Models\Commission;
use App\Models\Creator;
use App\Models\ListingImport;
use App\Models\Payment;
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
    public function compute(): array
    {
        $completedCheck = VehicleCheck::where('status', VehicleCheck::STATUS_COMPLETED)->where('type', VehicleCheck::TYPE_CHECK)->count();
        $completedPlus = VehicleCheck::where('status', VehicleCheck::STATUS_COMPLETED)->where('type', VehicleCheck::TYPE_PLUS)->count();
        $completedRebuild = VehicleCheck::where('status', VehicleCheck::STATUS_COMPLETED)->where('type', VehicleCheck::TYPE_REBUILD)->count();
        $failedChecks = VehicleCheck::where('status', VehicleCheck::STATUS_FAILED)->count();

        $revenue = (float) Payment::where('status', Payment::STATUS_PAID)->sum('gross');
        $revenueExVat = (float) Payment::where('status', Payment::STATUS_PAID)->sum('net');
        $paidPaymentsCount = Payment::where('status', Payment::STATUS_PAID)->count();

        // Every completed report (any tier) involves exactly one Full
        // Vehicle Check lookup — Plus's market valuation comes from the same
        // response, at no extra API cost.
        $costPerLookup = (float) config('valecheck.vehicle_data.vehiclematic.cost_per_lookup_net');
        $apiSpend = ($completedCheck + $completedPlus + $completedRebuild) * $costPerLookup;

        $aiSpend = (float) AiUsage::where('success', true)->get()
            ->sum(fn (AiUsage $usage) => (float) ($usage->actual_cost ?? $usage->estimated_cost ?? 0));

        $paymentProcessing = config('valecheck.payment_processing', ['percentage' => 0.015, 'fixed' => 0.20]);
        $paymentCost = ($revenue * $paymentProcessing['percentage']) + ($paidPaymentsCount * $paymentProcessing['fixed']);

        $totalCosts = $apiSpend + $aiSpend + $paymentCost;
        $contributionMargin = $revenue - $totalCosts;

        $avgPaymentCost = $paidPaymentsCount > 0 ? $paymentCost / $paidPaymentsCount : 0;
        $avgCostPerCheck = $completedCheck > 0 ? $costPerLookup + $avgPaymentCost : 0;
        $avgCostPerPlus = $completedPlus > 0 ? $costPerLookup + $avgPaymentCost : 0;
        $avgAiCostPerRebuild = $completedRebuild > 0 ? $aiSpend / $completedRebuild : 0;
        $avgCostPerRebuild = $completedRebuild > 0 ? $costPerLookup + $avgAiCostPerRebuild + $avgPaymentCost : 0;

        return [
            'users_count' => User::count(),
            'checks_completed' => $completedCheck,
            'plus_completed' => $completedPlus,
            'rebuild_completed' => $completedRebuild,
            'checks_failed' => $failedChecks,
            'active_subscriptions' => Subscription::where('stripe_status', 'active')->count(),

            'revenue' => $revenue,
            'revenue_ex_vat' => $revenueExVat,
            'api_spend' => $apiSpend,
            'ai_spend' => $aiSpend,
            'payment_processing_cost' => $paymentCost,
            'total_costs' => $totalCosts,
            'contribution_margin' => $contributionMargin,
            'contribution_margin_pct' => $revenue > 0 ? ($contributionMargin / $revenue) * 100 : 0,
            'avg_cost_per_check' => $avgCostPerCheck,
            'avg_cost_per_plus' => $avgCostPerPlus,
            'avg_cost_per_rebuild' => $avgCostPerRebuild,

            'failed_ai_calls' => AiUsage::where('success', false)->count(),
            'failed_checks' => $failedChecks,

            'affiliates_count' => Creator::count(),
            'referrals_count' => Referral::count(),
            'commissions_total' => (float) Commission::sum('amount'),

            'listing_import' => $this->listingImportStats(),
        ];
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
