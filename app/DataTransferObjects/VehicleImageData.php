<?php

namespace App\DataTransferObjects;

/**
 * Internal representation of a vehicle image lookup. A real photo of the
 * exact vehicle is never available from any provider — this is always a
 * stock/representative image matched by make/model/derivative/colour.
 */
final readonly class VehicleImageData
{
    public function __construct(
        public bool $available,
        public ?string $contents = null,
        public ?string $mimeType = null,
    ) {}
}
