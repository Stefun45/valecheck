<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Credits\CreditLedgerService;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use App\Services\Payments\StripeCheckoutService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extends Cashier's webhook controller (which already maintains the local
 * subscriptions/subscription_items tables) to add the one-off "checkout.session.completed"
 * handling Cashier doesn't cover, to grant that period's subscription
 * credits whenever a subscription invoice is paid, and to apply a
 * pending downgrade at the exact renewal boundary.
 */
class StripeWebhookController extends CashierWebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        app(StripeCheckoutCompletionHandler::class)->handle($payload['data']['object']);

        return $this->successMethod();
    }

    /**
     * Stripe sends this some time before the renewal invoice is actually
     * created - the only point at which a pending downgrade can be
     * applied and still have the upcoming renewal bill at the new
     * (lower) price. Applying it any later (e.g. on
     * invoice.payment_succeeded, after that invoice already exists)
     * would be one full billing cycle too late.
     */
    protected function handleInvoiceUpcoming(array $payload): Response
    {
        $data = $payload['data']['object'];
        $user = $this->getUserByStripeId($data['customer'] ?? null);

        if ($user && $user->pending_plan_id && ($data['subscription'] ?? null)) {
            app(StripeCheckoutService::class)->swapSubscription($user, $user->pendingPlan, prorate: false);
            $user->update(['pending_plan_id' => null]);
        }

        return $this->successMethod();
    }

    protected function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $response = parent::handleInvoicePaymentSucceeded($payload);

        $data = $payload['data']['object'];
        $user = $this->getUserByStripeId($data['customer'] ?? null);
        $subscriptionId = $data['subscription'] ?? null;

        if ($user && $subscriptionId) {
            $this->grantSubscriptionCredits($user, $data);
        }

        return $response;
    }

    /**
     * Stripe removed the top-level `price` field from invoice line items in
     * the 2025-03-31 ("basil") API version, replacing it with
     * `pricing.price_details.price` - this account is pinned to a later
     * version (see Cashier::STRIPE_VERSION), so only the newer shape is
     * ever actually sent; the older `price.id` path is kept only in case
     * Stripe ever includes it for backwards compatibility. Confirmed
     * against Stripe's own invoice-line-item object reference, and covered
     * by both shapes in SubscriptionWebhookTest. If neither path nor the
     * period can be found, or no plan matches, we skip rather than grant
     * credits against the wrong plan or period - a safe failure mode, but
     * one that should never actually trigger in normal operation.
     */
    private function grantSubscriptionCredits(User $user, array $invoice): void
    {
        $line = $invoice['lines']['data'][0] ?? [];
        $priceId = $line['price']['id'] ?? $line['pricing']['price_details']['price'] ?? null;
        $periodEndTimestamp = $line['period']['end'] ?? null;

        if (! $priceId || ! $periodEndTimestamp) {
            Log::warning("Could not determine subscription price/period from invoice.payment_succeeded for user #{$user->id} - skipping credit grant.");

            return;
        }

        $plan = SubscriptionPlan::where('stripe_price_id', $priceId)->first();

        if (! $plan) {
            Log::warning("No SubscriptionPlan matches Stripe price [{$priceId}] for user #{$user->id} - skipping credit grant.");

            return;
        }

        $expiresAt = Carbon::createFromTimestamp($periodEndTimestamp);

        // Idempotency: a retried webhook for the same invoice must never
        // grant twice. Keyed on user+plan+expiry rather than a separate
        // processed-invoice table - the same period can only ever be
        // granted once per plan.
        $alreadyGranted = CreditTransaction::where('user_id', $user->id)
            ->where('type', CreditTransaction::TYPE_SUBSCRIPTION_GRANT)
            ->where('subscription_plan_id', $plan->id)
            ->where('expires_at', $expiresAt)
            ->exists();

        if ($alreadyGranted) {
            return;
        }

        app(CreditLedgerService::class)->grantSubscriptionCredits($user, $plan, $plan->monthly_credits, $expiresAt);
    }
}
