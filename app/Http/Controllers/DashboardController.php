<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionUsage;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, CreditLedgerService $ledger)
    {
        $user = $request->user();

        $activeSubscriptionUsage = SubscriptionUsage::where('user_id', $user->id)
            ->where('report_type', VehicleCheck::TYPE_REBUILD)
            ->whereDate('period_start', '<=', now())
            ->whereDate('period_end', '>=', now())
            ->first();

        return view('dashboard', [
            'rebuildBalance' => $ledger->balance($user, VehicleCheck::TYPE_REBUILD),
            'activeSubscriptionUsage' => $activeSubscriptionUsage,
            'recentChecks' => $user->vehicleChecks()->with('vehicle')->latest()->take(10)->get(),
            'creditPacks' => config('valecheck.pricing.credit_packs'),
            'subscriptionPlans' => config('valecheck.pricing.subscriptions'),
            'isSubscribed' => $user->subscribed('default'),
        ]);
    }
}
