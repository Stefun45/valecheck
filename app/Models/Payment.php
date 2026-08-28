<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'type', 'description', 'gross', 'net', 'vat', 'vat_rate', 'currency',
    'stripe_checkout_session_id', 'stripe_payment_intent_id', 'status', 'refunded_at',
    'discount_code_id', 'original_gross',
])]
class Payment extends Model
{
    public const TYPE_CHECK = 'check';

    public const TYPE_PLUS = 'plus';

    public const TYPE_REBUILD = 'rebuild';

    public const TYPE_PLUS_UPGRADE = 'plus_upgrade';

    public const TYPE_CREDIT_PACK = 'credit_pack';

    public const TYPE_SUBSCRIPTION = 'subscription';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
            'net' => 'decimal:2',
            'vat' => 'decimal:2',
            'vat_rate' => 'decimal:4',
            'original_gross' => 'decimal:2',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }
}
