<?php

namespace App\Services\ListingImport;

use App\DataTransferObjects\ListingImportResult;

/**
 * AutoTrader's robots.txt disallows both /car-search and /car-details* for
 * every user-agent (User-agent: * block) — confirmed by fetching the real
 * robots.txt, not assumed. Independently, its search pages are a
 * client-rendered SPA shell with no server-rendered metadata anyway. This
 * provider recognises AutoTrader URLs but never attempts to fetch them.
 */
class AutoTraderListingProvider implements ListingImportProvider
{
    private const HOST = 'autotrader.co.uk';

    public function supports(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === self::HOST || $host === 'www.'.self::HOST || str_ends_with($host, '.'.self::HOST);
    }

    public function import(string $url): ListingImportResult
    {
        return ListingImportResult::blocked(
            'autotrader',
            "AutoTrader's robots.txt disallows automated access to both search and individual listing pages. ValeCheck does not attempt to bypass this — please continue manually."
        );
    }
}
