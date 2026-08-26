<?php

namespace App\Services\ListingImport;

use App\DataTransferObjects\ListingImportResult;

/**
 * eBay's own robots.txt states, in prose, that automated scraping is
 * "strictly prohibited", and a direct non-browser request to a search page
 * returns HTTP 403 — confirmed by real testing, not assumed. This provider
 * therefore recognises eBay URLs but never attempts to fetch them; it
 * declines immediately, which is the graceful-failure behaviour the brief
 * itself asks for when a marketplace blocks automated retrieval.
 */
class EbayListingProvider implements ListingImportProvider
{
    private const HOST_SUFFIXES = ['ebay.co.uk', 'ebay.com'];

    public function supports(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach (self::HOST_SUFFIXES as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return true;
            }
        }

        return false;
    }

    public function import(string $url): ListingImportResult
    {
        return ListingImportResult::blocked(
            'ebay',
            "eBay's robots.txt prohibits automated scraping and its listing pages return HTTP 403 to non-browser requests. ValeCheck does not attempt to bypass this — please continue manually."
        );
    }
}
