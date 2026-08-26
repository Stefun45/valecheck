<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'plan', 'report_type', 'period_start', 'period_end', 'allowance', 'used'])]
class SubscriptionUsage extends Model
{
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function hasRemainingAllowance(): bool
    {
        return is_null($this->allowance) || $this->used < $this->allowance;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
