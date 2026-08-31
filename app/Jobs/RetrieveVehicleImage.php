<?php

namespace App\Jobs;

use App\Models\VehicleCheck;
use App\Services\VehicleImagery\VehicleImageProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Plus and Rebuild only — see VehicleCheckPipeline. Check keeps the
 * generic silhouette, since a real image is a paid One Auto call.
 *
 * Never fails the check if unavailable — a missing vehicle image falls
 * back to the silhouette in the report views, it's cosmetic only.
 */
class RetrieveVehicleImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $vehicleCheckId) {}

    public function handle(VehicleImageProvider $provider): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $vehicleData = $check->toVehicleData();

        $image = $provider->fetch($check->registration, $vehicleData->colour, $check->id);

        if (! $image->available || $image->contents === null) {
            return;
        }

        $disk = config('valecheck.reports.pdf_disk');
        $extension = str($image->mimeType ?? 'image/png')->after('/')->explode('+')->first() ?: 'png';
        $path = "reports/{$check->id}/vehicle-image.{$extension}";

        Storage::disk($disk)->put($path, $image->contents);

        $check->update([
            'vehicle_image_disk' => $disk,
            'vehicle_image_path' => $path,
        ]);
    }
}
