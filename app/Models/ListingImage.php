<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['listing_import_id', 'source_url', 'disk', 'path', 'hash', 'position', 'status'])]
class ListingImage extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DOWNLOADED = 'downloaded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED_DUPLICATE = 'skipped_duplicate';

    public const STATUS_SKIPPED_OVER_LIMIT = 'skipped_over_limit';

    public function listingImport(): BelongsTo
    {
        return $this->belongsTo(ListingImport::class);
    }

    public function temporaryUrl(): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes(30));
    }
}
