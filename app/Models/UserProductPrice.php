<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-account override of what one specific user pays for a product,
 * taking priority over the standard ProductPrice everyone else pays.
 * Absence of a row for a (user, type) pair means "standard pricing" —
 * this table only ever holds the exceptions, not every user's price.
 */
#[Fillable(['user_id', 'type', 'gross'])]
class UserProductPrice extends Model
{
    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
