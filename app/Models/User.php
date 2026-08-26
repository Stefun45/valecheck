<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
#[Fillable(['name', 'email', 'password'])]
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

    public function subscriptionUsages(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function creator(): HasOne
    {
        return $this->hasOne(Creator::class);
    }
}
