<?php

namespace App\Http\Controllers;

use App\Services\Payments\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    public function creditPack(Request $request, StripeCheckoutService $checkoutService)
    {
        // Every credit pack only ever grants Rebuild allowance — hidden
        // while Rebuild itself is hidden from launch.
        abort_unless(config('valecheck.rebuild_enabled'), 404);

        $this->ensureStripeConfigured();

        $validated = $request->validate([
            'pack' => ['required', 'string', 'in:'.implode(',', array_keys(config('valecheck.pricing.credit_packs')))],
        ]);

        return $checkoutService->checkoutForCreditPack($request->user(), $validated['pack'])->redirect();
    }

    public function subscription(Request $request, StripeCheckoutService $checkoutService)
    {
        // Every subscription plan only ever grants Rebuild allowance —
        // hidden while Rebuild itself is hidden from launch.
        abort_unless(config('valecheck.rebuild_enabled'), 404);

        $this->ensureStripeConfigured();

        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:'.implode(',', array_keys(config('valecheck.pricing.subscriptions')))],
        ]);

        return $checkoutService->checkoutForSubscription($request->user(), $validated['plan'])->redirect();
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
