<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'url', 'url_hash', 'domain', 'provider', 'status', 'data',
    'image_count_found', 'images_capped', 'http_status', 'error_message',
    'duration_ms', 'user_id',
])]
class ListingImport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BLOCKED = 'blocked';

    public const TERMINAL_STATUSES = [
        self::STATUS_SUCCESS, self::STATUS_PARTIAL, self::STATUS_FAILED, self::STATUS_BLOCKED,
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'images_capped' => 'boolean',
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('position');
    }
}
