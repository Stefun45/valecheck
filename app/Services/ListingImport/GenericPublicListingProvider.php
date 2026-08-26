<?php

namespace App\Services\ListingImport;

use App\DataTransferObjects\ListingImportResult;

/**
 * Catch-all provider: attempts JSON-LD/OpenGraph/meta-tag extraction on any
 * http(s) URL. Registered last in ListingImportService so a domain-specific
 * provider always gets first refusal.
 */
class GenericPublicListingProvider extends AbstractMetadataListingProvider
{
    public function supports(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    public function import(string $url): ListingImportResult
    {
        return $this->importFromUrl($url, 'generic');
    }
}
