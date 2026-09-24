<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\Payments\StripeCheckoutService;
use App\Services\Subscriptions\SubscriptionPlanChangeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    public function creditPack(Request $request, StripeCheckoutService $checkoutService)
    {
        $this->ensureStripeConfigured();

        $validated = $request->validate([
            'pack' => ['required', 'string', 'in:'.implode(',', array_keys(config('valecheck.pricing.credit_packs')))],
        ]);

        return $checkoutService->checkoutForCreditPack($request->user(), $validated['pack'])->redirect();
    }

    public function subscription(Request $request, StripeCheckoutService $checkoutService, SubscriptionPlanChangeService $planChanges)
    {
        abort_unless(config('valecheck.subscriptions_enabled'), 404);

        $this->ensureStripeConfigured();

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
        ]);

        $user = $request->user();
        $newPlan = SubscriptionPlan::findOrFail($validated['plan_id']);

        if (! $user->subscribed('default')) {
            return $checkoutService->checkoutForSubscription($user, $newPlan)->redirect();
        }

        $currentPlan = $user->activeSubscriptionPlan();

        // Downgrade (or an equal-tier switch, which can't happen given
        // distinct sort_order per plan) never touches Stripe or credits
        // immediately - see SubscriptionPlanChangeService.
        if ($currentPlan && $newPlan->isDowngradeFrom($currentPlan)) {
            $planChanges->requestDowngrade($user, $newPlan);

            return redirect()->route('dashboard')->with('status', "Your plan will change to {$newPlan->name} at your next renewal.");
        }

        $planChanges->upgrade($user, $newPlan);

        return redirect()->route('dashboard')->with('status', 'Your plan has been changed.');
    }

    public function additionalCredits(Request $request, StripeCheckoutService $checkoutService)
    {
        abort_unless(config('valecheck.subscriptions_enabled'), 404);

        $this->ensureStripeConfigured();

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $user = $request->user();
        $plan = $user->activeSubscriptionPlan();

        abort_if($plan === null, 403, 'An active subscription is required to buy additional credits.');

        return $checkoutService->checkoutForAdditionalCredits($user, $plan, $validated['quantity'])->redirect();
    }

    public function portal(Request $request)
    {
        abort_unless(config('valecheck.subscriptions_enabled'), 404);

        $this->ensureStripeConfigured();

        return $request->user()->redirectToBillingPortal(route('dashboard'));
    }

    private function ensureStripeConfigured(): void
    {
        if (empty(config('cashier.secret'))) {
            throw ValidationException::withMessages([
                'stripe' => 'Payments are not configured yet — add STRIPE_KEY and STRIPE_SECRET to .env.',
            ]);
        }
    }
}
