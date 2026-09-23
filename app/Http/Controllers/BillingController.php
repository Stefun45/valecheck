<?php

namespace App\Http\Controllers;

use App\Services\Payments\StripeCheckoutService;
use Illuminate\Http\Request;
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

    public function subscription(Request $request, StripeCheckoutService $checkoutService)
    {
        abort_unless(config('valecheck.subscriptions_enabled'), 404);

        $this->ensureStripeConfigured();

        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:'.implode(',', array_keys(config('valecheck.pricing.subscriptions')))],
        ]);

        $user = $request->user();

        // Already subscribed - changing plan is an in-place Stripe swap,
        // never a second Checkout Session (Cashier's newSubscription()
        // would otherwise happily create a duplicate subscription).
        if ($user->subscribed('default')) {
            $checkoutService->swapSubscription($user, $validated['plan']);

            return redirect()->route('dashboard')->with('status', 'Your plan has been changed.');
        }

        return $checkoutService->checkoutForSubscription($user, $validated['plan'])->redirect();
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
