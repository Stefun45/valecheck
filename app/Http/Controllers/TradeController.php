<?php

namespace App\Http\Controllers;

use App\Mail\TradeInterestEmail;
use App\Models\SubscriptionPlan;
use App\Services\Pricing\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Public, unauthenticated trade/dealer landing page — distinct from
 * billing/trader-verification (which handles an already-registered
 * customer actually getting verified). Shows the real, admin-configured
 * plan lineup; each row links straight to checkout (via the same
 * billing.subscribe route the dashboard uses) once subscriptions are
 * enabled (see config('valecheck.subscriptions_enabled')) — until then it
 * falls back to a "coming soon" badge and collects interest via email.
 */
class TradeController extends Controller
{
    public function show(PricingService $pricing): View
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'plan' => $plan,
                'price' => $pricing->forSubscriptionPlan($plan),
            ]);

        return view('trade', ['plans' => $plans]);
    }

    public function submitInterest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        Mail::to(config('valecheck.enterprise_contact_email'))->send(new TradeInterestEmail($validated));

        return back()->with('status', "Thanks — we'll let you know as soon as trade plans launch.");
    }
}
