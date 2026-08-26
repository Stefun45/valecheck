<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionUsage;
use App\Models\User;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extends Cashier's webhook controller (which already maintains the local
 * subscriptions/subscription_items tables) to add the one-off "checkout.session.completed"
 * handling Cashier doesn't cover, and to open a SubscriptionUsage window whenever
 * a subscription invoice is paid.
 */
class StripeWebhookController extends CashierWebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        app(StripeCheckoutCompletionHandler::class)->handle($payload['data']['object']);

        return $this->successMethod();
    }

    protected function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $response = parent::handleInvoicePaymentSucceeded($payload);

        $data = $payload['data']['object'];
        $user = $this->getUserByStripeId($data['customer'] ?? null);
        $subscriptionId = $data['subscription'] ?? null;

        if ($user && $subscriptionId) {
            $this->openSubscriptionUsageWindow($user, $data);
        }

        return $response;
    }

    /**
     * Best-effort: Stripe's invoice line-item JSON shape has changed across
     * API versions, so the exact path to the price ID and billing period
     * is not guaranteed here without testing against a real webhook payload
     * (e.g. via `stripe trigger invoice.payment_succeeded` with the Stripe
     * CLI once real test-mode keys are configured). If either can't be
     * found, we skip rather than create a SubscriptionUsage row with wrong
     * dates — the user simply won't get subscription-funded reports until
     * this is verified against a live payload, which is a safe failure mode.
     */
    private function openSubscriptionUsageWindow(User $user, array $invoice): void
    {
        $line = $invoice['lines']['data'][0] ?? [];
        $priceId = $line['price']['id'] ?? $line['pricing']['price_details']['price'] ?? null;

        $plan = collect(config('valecheck.pricing.subscriptions'))
            ->search(fn ($subscription) => $subscription['stripe_price'] === $priceId);

        $periodStartTimestamp = $line['period']['start'] ?? null;
        $periodEndTimestamp = $line['period']['end'] ?? null;

        if ($plan === false || $plan === null || ! $periodStartTimestamp || ! $periodEndTimestamp) {
            Log::warning("Could not determine subscription plan/period from invoice.payment_succeeded for user #{$user->id} — skipping SubscriptionUsage window.");

            return;
        }

        // Subscriptions currently only grant a Rebuild allowance, but the
        // schema (report_type column, config keyed by product) supports more
        // than one product per plan without a future migration.
        $reportType = 'rebuild';

        SubscriptionUsage::firstOrCreate(
            [
                'user_id' => $user->id,
                'report_type' => $reportType,
                'period_start' => Carbon::createFromTimestamp($periodStartTimestamp)->toDateString(),
                'period_end' => Carbon::createFromTimestamp($periodEndTimestamp)->toDateString(),
            ],
            [
                'plan' => $plan,
                'allowance' => config("valecheck.pricing.subscriptions.{$plan}.allowances.{$reportType}"),
                'used' => 0,
            ]
        );
    }
}
