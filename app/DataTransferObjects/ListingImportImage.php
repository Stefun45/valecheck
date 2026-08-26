<?php

namespace App\DataTransferObjects;

final readonly class ListingImportImage
{
    public function __construct(
        public string $url,
        public int $order,
    ) {}
}
