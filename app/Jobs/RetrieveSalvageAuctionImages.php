<?php

namespace App\Jobs;

use App\Models\SalvageAuctionImage;
use App\Models\VehicleCheck;
use App\Services\ListingImport\SafeUrlFetcher;
use App\Services\SalvageAuction\SalvageAuctionImageProvider;
use finfo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * ValeCheck Plus/Rebuild only — runs immediately after
 * RetrieveSalvageAuctionHistory in both VehicleCheckPipeline chains, since
 * it depends on the SalvageAuctionCheck row that job just wrote.
 *
 * Downloads and permanently stores salvage-auction photos so they can be
 * embedded directly in the report rather than linked out to a One Auto URL
 * that expires after ~12 hours (see SalvageAuctionImage).
 *
 * For a record whose primary carguide/salvagecheck/v2 call already
 * returned image URLs (under 5% of records, per One Auto), those are
 * downloaded directly here at no extra cost. The separate, paid
 * salvageguide/thirdpartyimages endpoint is only called for a record that
 * still has none — a record's images are never paid for twice.
 *
 * A failed/unavailable fetch degrades to no images for that record rather
 * than failing the whole report — this is additive provenance, not a
 * safety-critical field, the same philosophy as RetrieveSalvageAuctionHistory.
 */
class RetrieveSalvageAuctionImages implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $vehicleCheckId) {}

    public function handle(SalvageAuctionImageProvider $provider, SafeUrlFetcher $fetcher): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $salvageCheck = $check->salvageAuctionCheck;

        if (! $salvageCheck || ! $salvageCheck->record_found) {
            return;
        }

        foreach ($salvageCheck->records as $index => $record) {
            $recordId = $record['recordId'] ?? null;

            if ($recordId === null) {
                continue;
            }

            $imageUrls = $record['imageUrls'] ?? [];

            // Already have URLs from the primary (already-paid-for) call —
            // download those directly rather than paying for the separate
            // image endpoint on top of it.
            if ($imageUrls === []) {
                $imageUrls = $provider->fetchImages((string) $recordId, $check->id);
            }

            $this->downloadImages($salvageCheck->id, $index, $imageUrls, $fetcher);
        }
    }

    /**
     * @param  array<int, string>  $imageUrls
     */
    private function downloadImages(int $salvageAuctionCheckId, int $recordIndex, array $imageUrls, SafeUrlFetcher $fetcher): void
    {
        $position = 0;

        foreach ($imageUrls as $imageUrl) {
            try {
                $bytes = $fetcher->fetchBinary($imageUrl);
            } catch (\Throwable) {
                // SafeUrlFetcher only swallows an SSRF block itself — a
                // genuine transport failure (DNS, connection refused, a
                // retry still failing) propagates as an exception. This is
                // additive provenance, not a safety-critical field, so one
                // bad photo URL must never fail the whole report.
                continue;
            }

            if ($bytes === null) {
                continue;
            }

            $extension = $this->guessExtension($bytes) ?? 'jpg';
            $path = "salvage-auction/{$salvageAuctionCheckId}/{$recordIndex}-{$position}.{$extension}";
            Storage::disk('local')->put($path, $bytes);

            SalvageAuctionImage::create([
                'salvage_auction_check_id' => $salvageAuctionCheckId,
                'record_index' => $recordIndex,
                'source_url' => $imageUrl,
                'disk' => 'local',
                'path' => $path,
                'position' => $position,
            ]);

            $position++;
        }
    }

    private function guessExtension(string $bytes): ?string
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };
    }
}
