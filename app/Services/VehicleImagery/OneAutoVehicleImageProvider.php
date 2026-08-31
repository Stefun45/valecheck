<?php

namespace App\Services\VehicleImagery;

use App\DataTransferObjects\VehicleImageData;
use App\Services\OneAuto\OneAutoApiException;
use App\Services\OneAuto\OneAutoClient;
use Illuminate\Support\Facades\Http;

/**
 * Real vehicle imagery via One Auto's vehicleimagery product. Two calls:
 * a search by VRM (returns image IDs per view angle plus the colour
 * variants actually available for this vehicle), then a resolve-by-ID
 * call for a chosen angle/colour that returns a temporary CDN URL.
 *
 * That CDN URL expires after 7 days — well inside a report's retention
 * window — so the bytes are downloaded immediately here and stored
 * permanently by the caller, rather than the URL itself being kept.
 */
class OneAutoVehicleImageProvider implements VehicleImageProvider
{
    private const SEARCH_ENDPOINT = 'vehicleimagery/imagesearchfromvrm';

    private const RESOLVE_ENDPOINT = 'vehicleimagery/imagefromid';

    public function __construct(private readonly OneAutoClient $client) {}

    public function fetch(string $registration, ?string $colour, ?int $vehicleCheckId = null): VehicleImageData
    {
        try {
            $search = $this->client->get(self::SEARCH_ENDPOINT, $registration, [
                'vehicle_registration_mark' => $registration,
            ], $vehicleCheckId);
        } catch (OneAutoApiException) {
            return new VehicleImageData(available: false);
        }

        $imageSet = $search['images'][0] ?? null;
        $imageId = $imageSet['image_ids']['front_right'] ?? null;

        if ($imageId === null) {
            return new VehicleImageData(available: false);
        }

        $chosenColour = $this->matchColour($colour, $imageSet['colour_desc_list'] ?? []);

        try {
            $resolved = $this->client->get(self::RESOLVE_ENDPOINT, $registration, [
                'image_id' => $imageId,
                'generic_colour_desc' => $chosenColour,
                'image_background' => 'Transparent',
            ], $vehicleCheckId);
        } catch (OneAutoApiException) {
            return new VehicleImageData(available: false);
        }

        $imageUrl = $resolved['image_url'] ?? null;

        if ($imageUrl === null) {
            return new VehicleImageData(available: false);
        }

        try {
            $response = Http::timeout(10)->get($imageUrl);
        } catch (\Throwable) {
            return new VehicleImageData(available: false);
        }

        if ($response->failed()) {
            return new VehicleImageData(available: false);
        }

        return new VehicleImageData(
            available: true,
            contents: $response->body(),
            mimeType: $response->header('Content-Type') ?: 'image/png',
        );
    }

    /**
     * @param  string[]  $available
     */
    private function matchColour(?string $colour, array $available): string
    {
        if ($colour !== null) {
            foreach ($available as $option) {
                if (strtolower($option) === strtolower($colour)) {
                    return $option;
                }
            }
        }

        return $available[0] ?? 'White';
    }
}
