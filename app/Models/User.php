<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

// Email verification is not required to use the app — Check/Plus purchases
// are gated by Stripe payment anyway, and Rebuild's old free-credit-on-
// verification incentive no longer exists. Not implementing
// Illuminate\Contracts\Auth\MustVerifyEmail here is what disables the
// `verified` middleware everywhere at once (it becomes a no-op when the
// user model doesn't implement the contract) — the underlying
// verification mechanism (hasVerifiedEmail(), the /verify-email routes)
// still works if ever needed again, it's just not required.
#[Fillable(['name', 'email', 'password', 'pending_plan_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function vehicleChecks(): HasMany
    {
        return $this->hasMany(VehicleCheck::class);
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function productPriceOverrides(): HasMany
    {
        return $this->hasMany(UserProductPrice::class);
    }

    /**
     * The plan whose monthly credit allocation is still valid today,
     * resolved from the ledger itself (the most recent non-expired
     * TYPE_SUBSCRIPTION_GRANT) rather than a separate "current
     * subscription" record - there's no other source of truth for which
     * plan a user is actually on right now.
     */
    public function activeSubscriptionPlan(): ?SubscriptionPlan
    {
        $planId = $this->creditTransactions()
            ->where('type', CreditTransaction::TYPE_SUBSCRIPTION_GRANT)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->value('subscription_plan_id');

        return $planId ? SubscriptionPlan::find($planId) : null;
    }

    /**
     * Gates trade-sector-restricted report content (e.g. high-risk
     * markers) — must never be inferred from anything else (report type,
     * funding source, credit balance), only an active Dealer-group
     * subscription for today's billing period AND an approved
     * TraderVerification. Payment alone was never proof of being a
     * genuine trader - see TraderVerification.
     */
    public function hasVerifiedTradeAccess(): bool
    {
        if (! $this->isVerifiedTrader()) {
            return false;
        }

        return $this->activeSubscriptionPlan()?->group === SubscriptionPlan::GROUP_DEALER;
    }

    public function isVerifiedTrader(): bool
    {
        return $this->traderVerification?->isApproved() ?? false;
    }

    public function traderVerification(): HasOne
    {
        return $this->hasOne(TraderVerification::class);
    }

    public function creator(): HasOne
    {
        return $this->hasOne(Creator::class);
    }

    /**
     * Set only while a downgrade is awaiting the next renewal - see
     * StripeWebhookController::grantSubscriptionCredits(), the only place
     * this is ever applied and cleared.
     */
    public function pendingPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'pending_plan_id');
    }
}
