<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per user - a resubmission after rejection updates this same
 * row back to pending rather than creating a second one (see the
 * unique user_id constraint on the migration). Approval is what
 * User::hasVerifiedTradeAccess() checks alongside an active Trader/
 * Dealer subscription before showing trade-restricted report content
 * (e.g. the Experian high-risk marker) - a live subscription alone was
 * never proof of being a genuine trader.
 */
#[Fillable(['user_id', 'company_name', 'company_number', 'vat_number', 'status', 'notes', 'submitted_at', 'reviewed_at', 'reviewed_by'])]
class TraderVerification extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
