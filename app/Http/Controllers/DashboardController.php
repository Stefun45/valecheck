<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionUsage;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, CreditLedgerService $ledger, PricingService $pricing)
    {
        $user = $request->user();

        $activeSubscriptionUsage = SubscriptionUsage::where('user_id', $user->id)
            ->where('report_type', VehicleCheck::TYPE_REBUILD)
            ->whereDate('period_start', '<=', now())
            ->whereDate('period_end', '>=', now())
            ->first();

        $creditPacks = collect(config('valecheck.pricing.credit_packs'))
            ->map(fn ($pack, $key) => array_merge($pack, ['price' => $pricing->forCreditPack($key)]))
            ->all();

        return view('dashboard', [
            'plusBalance' => $ledger->balance($user, VehicleCheck::TYPE_PLUS),
            'activeSubscriptionUsage' => $activeSubscriptionUsage,
            'recentChecks' => $user->vehicleChecks()->with('vehicle')->latest()->take(10)->get(),
            'creditPacks' => $creditPacks,
            'subscriptionPlans' => config('valecheck.pricing.subscriptions'),
            'isSubscribed' => $user->subscribed('default'),
        ]);
    }
}
