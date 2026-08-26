<?php

namespace App\Jobs;

use App\DataTransferObjects\ListingImportResult;
use App\Models\ListingImage;
use App\Models\ListingImport;
use App\Services\ListingImport\ListingImportService;
use App\Services\ListingImport\SafeUrlFetcher;
use finfo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

class ImportListing implements ShouldQueue
{
    use Queueable;

    // No automatic Laravel-level retry against someone else's server — the
    // fetcher itself retries once, internally, but only for genuine
    // transient network errors, never for a deliberate block.
    public int $tries = 1;

    public function __construct(public int $listingImportId) {}

    public function handle(ListingImportService $importService, SafeUrlFetcher $fetcher): void
    {
        $listingImport = ListingImport::findOrFail($this->listingImportId);
        $listingImport->update(['status' => 'processing']);

        $throttleKey = 'listing-import:'.$listingImport->domain;
        $limits = config('valecheck.listing_import.rate_limit_per_domain');

        if (RateLimiter::tooManyAttempts($throttleKey, $limits['attempts'])) {
            $listingImport->update([
                'status' => ListingImport::STATUS_FAILED,
                'error_message' => 'Too many import requests for this domain right now — please try again shortly, or continue manually.',
            ]);

            return;
        }

        RateLimiter::hit($throttleKey, $limits['decay_seconds']);

        $startedAt = microtime(true);
        $result = $importService->import($listingImport->url);
        $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

        $listingImport->update([
            'provider' => $result->provider,
            'status' => $result->status,
            'data' => $result->fields,
            'http_status' => $result->httpStatus,
            'error_message' => $result->errorMessage,
            'duration_ms' => $durationMs,
        ]);

        if (in_array($result->status, [ListingImportResult::STATUS_SUCCESS, ListingImportResult::STATUS_PARTIAL], true)) {
            $this->downloadImages($listingImport, $result, $fetcher);
        }
    }

    private function downloadImages(ListingImport $listingImport, ListingImportResult $result, SafeUrlFetcher $fetcher): void
    {
        $maxImages = (int) config('valecheck.listing_import.max_images', 30);
        $seenHashes = [];
        $downloaded = 0;
        $capped = false;

        foreach ($result->images as $image) {
            if ($downloaded >= $maxImages) {
                ListingImage::create([
                    'listing_import_id' => $listingImport->id,
                    'source_url' => $image->url,
                    'position' => $image->order,
                    'status' => ListingImage::STATUS_SKIPPED_OVER_LIMIT,
                ]);
                $capped = true;

                continue;
            }

            $bytes = $fetcher->fetchBinary($image->url);

            if ($bytes === null) {
                ListingImage::create([
                    'listing_import_id' => $listingImport->id,
                    'source_url' => $image->url,
                    'position' => $image->order,
                    'status' => ListingImage::STATUS_FAILED,
                ]);

                continue;
            }

            $hash = hash('sha256', $bytes);

            if (in_array($hash, $seenHashes, true)) {
                ListingImage::create([
                    'listing_import_id' => $listingImport->id,
                    'source_url' => $image->url,
                    'hash' => $hash,
                    'position' => $image->order,
                    'status' => ListingImage::STATUS_SKIPPED_DUPLICATE,
                ]);

                continue;
            }

            $seenHashes[] = $hash;
            $path = "listing-import/{$listingImport->id}/{$hash}.".($this->guessExtension($bytes) ?? 'jpg');
            Storage::disk('local')->put($path, $bytes);

            ListingImage::create([
                'listing_import_id' => $listingImport->id,
                'source_url' => $image->url,
                'disk' => 'local',
                'path' => $path,
                'hash' => $hash,
                'position' => $image->order,
                'status' => ListingImage::STATUS_DOWNLOADED,
            ]);

            $downloaded++;
        }

        $listingImport->update([
            'image_count_found' => count($result->images),
            'images_capped' => $capped,
        ]);
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
