<?php

namespace App\Services\ListingImport;

use App\DataTransferObjects\ListingImportResult;

interface ListingImportProvider
{
    public function supports(string $url): bool;

    public function import(string $url): ListingImportResult;
}
