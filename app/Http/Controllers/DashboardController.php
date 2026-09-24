<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\SubscriptionPlan;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, CreditLedgerService $ledger, PricingService $pricing)
    {
        $user = $request->user();
        $plusBalance = $ledger->balance($user, VehicleCheck::TYPE_PLUS);
        $activePlan = $user->activeSubscriptionPlan();

        $activeGrant = $user->creditTransactions()
            ->where('type', CreditTransaction::TYPE_SUBSCRIPTION_GRANT)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->first();

        // "Credits remaining from your plan" vs "additional credits" is a
        // display convention, not a tracked distinction - consumption is
        // one flat ledger, not attributed to a specific grant. Treating
        // the subscription's own allocation as the base and anything the
        // total balance exceeds it by as "additional" is simple, honest,
        // and matches how the balance actually behaves as it's spent.
        $subscriptionGrantTotal = $activeGrant?->amount ?? 0;
        $subscriptionCreditsRemaining = min($subscriptionGrantTotal, $plusBalance);
        $additionalCreditsRemaining = max(0, $plusBalance - $subscriptionCreditsRemaining);

        $additionalCreditsPurchased = (int) $user->creditTransactions()
            ->where('type', CreditTransaction::TYPE_PURCHASE)
            ->where('report_type', VehicleCheck::TYPE_PLUS)
            ->sum('amount');

        $creditPacks = collect(config('valecheck.pricing.credit_packs'))
            ->map(fn ($pack, $key) => array_merge($pack, ['price' => $pricing->forCreditPack($key)]))
            ->all();

        $subscriptionPlans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'plan' => $plan,
                'price' => $pricing->forSubscriptionPlan($plan),
                'additionalCreditPrice' => $pricing->forAdditionalCredit($plan),
            ]);

        // Dealer group is the only one trade verification applies to - Pro
        // is just a bigger report bundle, never worth prompting for
        // business details it has no use for.
        $needsTraderVerification = $activePlan
            && $activePlan->group === SubscriptionPlan::GROUP_DEALER
            && ! $user->isVerifiedTrader();

        return view('dashboard', [
            'plusBalance' => $plusBalance,
            'plusPrice' => $pricing->forPlus()->gross,
            'activePlan' => $activePlan,
            'activePlanAdditionalCreditPrice' => $activePlan ? $pricing->forAdditionalCredit($activePlan)->gross : null,
            'pendingPlan' => $user->pendingPlan,
            'renewalDate' => $activeGrant?->expires_at,
            'subscriptionCreditsRemaining' => $subscriptionCreditsRemaining,
            'subscriptionGrantTotal' => $subscriptionGrantTotal,
            'additionalCreditsRemaining' => $additionalCreditsRemaining,
            'additionalCreditsPurchased' => $additionalCreditsPurchased,
            'recentChecks' => $user->vehicleChecks()->with('vehicle')->latest()->take(10)->get(),
            'creditPacks' => $creditPacks,
            'subscriptionPlans' => $subscriptionPlans,
            'isSubscribed' => $user->subscribed('default'),
            'traderVerification' => $user->traderVerification,
            'needsTraderVerification' => $needsTraderVerification,
        ]);
    }
}
