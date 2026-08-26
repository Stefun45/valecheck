<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'vehicle_check_id', 'type', 'headline_summary', 'listing_gaps', 'risks', 'things_to_check',
    'listing_vs_evidence', 'generated_at', 'pdf_disk', 'pdf_path', 'pdf_generated_at',
])]
class Report extends Model
{
    protected function casts(): array
    {
        return [
            'listing_gaps' => 'array',
            'risks' => 'array',
            'things_to_check' => 'array',
            'listing_vs_evidence' => 'array',
            'generated_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }

    public function hasPdf(): bool
    {
        return $this->pdf_path !== null && Storage::disk($this->pdf_disk)->exists($this->pdf_path);
    }

    public function pdfTemporaryUrl(): string
    {
        return Storage::disk($this->pdf_disk)->temporaryUrl($this->pdf_path, now()->addMinutes(10));
    }
}
