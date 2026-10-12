<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A salvage-auction photo downloaded from One Auto's thirdpartyimages
 * endpoint (or, for the rare record where the primary salvage check call
 * already returned image URLs, downloaded directly from those) and stored
 * permanently - see OneAutoSalvageAuctionImageProvider and
 * RetrieveSalvageAuctionImages for why these are downloaded rather than
 * linked to the original source, which expires after ~12 hours.
 */
#[Fillable(['salvage_auction_check_id', 'record_index', 'source_url', 'disk', 'path', 'position'])]
class SalvageAuctionImage extends Model
{
    public function salvageAuctionCheck(): BelongsTo
    {
        return $this->belongsTo(SalvageAuctionCheck::class);
    }

    /**
     * Same pattern as ListingImage/VehicleCheckImage::temporaryUrl() - the
     * 'local' disk has 'serve' => true (see config/filesystems.php), so this
     * resolves to a short-lived signed URL served by Laravel itself rather
     * than requiring a cloud disk.
     */
    public function temporaryUrl(): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes(30));
    }

    /**
     * Base64 data URI for the PDF - dompdf has isRemoteEnabled disabled
     * (see ReportPdfService), so a normal image URL (even our own signed
     * one) can't be fetched there; embedding the bytes directly sidesteps
     * that entirely. Mirrors VehicleCheck::vehicleImageDataUri().
     */
    public function dataUri(): string
    {
        $disk = Storage::disk($this->disk);
        $mime = $disk->mimeType($this->path) ?: 'image/jpeg';
        $contents = $disk->get($this->path);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
