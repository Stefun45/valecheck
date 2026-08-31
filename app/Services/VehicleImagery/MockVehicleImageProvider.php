<?php

namespace App\Services\VehicleImagery;

use App\DataTransferObjects\VehicleImageData;

/**
 * Generates a plain placeholder image locally via GD rather than always
 * returning unavailable — this keeps the report-header/cover-page image
 * slot visibly wired up in local dev without downloading any real stock
 * photography (which would carry the same licensing risk the real
 * provider exists to avoid) or bundling a fixture file.
 */
class MockVehicleImageProvider implements VehicleImageProvider
{
    private const COLOUR_MAP = [
        'black' => [30, 30, 30],
        'white' => [235, 235, 235],
        'silver' => [180, 180, 185],
        'grey' => [120, 120, 125],
        'gray' => [120, 120, 125],
        'blue' => [40, 70, 160],
        'red' => [170, 30, 35],
        'green' => [30, 100, 60],
    ];

    public function fetch(string $registration, ?string $colour, ?int $vehicleCheckId = null): VehicleImageData
    {
        $rgb = self::COLOUR_MAP[strtolower($colour ?? '')] ?? [90, 95, 105];

        $image = imagecreatetruecolor(640, 400);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));

        $textColour = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, 20, 20, strtoupper($registration), $textColour);
        imagestring($image, 3, 20, 40, 'MOCK VEHICLE IMAGE', $textColour);

        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return new VehicleImageData(
            available: true,
            contents: $contents,
            mimeType: 'image/png',
        );
    }
}
