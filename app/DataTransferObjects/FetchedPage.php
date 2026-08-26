<?php

namespace App\DataTransferObjects;

final readonly class FetchedPage
{
    public function __construct(
        public string $finalUrl,
        public int $statusCode,
        public string $body,
        public ?string $contentType,
    ) {}
}
