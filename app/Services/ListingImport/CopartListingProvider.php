<?php

namespace App\Services\ListingImport;

use App\DataTransferObjects\ListingImportResult;

/**
 * Unlike eBay/AutoTrader, Copart's robots.txt does not blanket-disallow
 * general lot pages (confirmed by fetching the real robots.txt) — it only
 * disallows specific account/bidding/dashboard paths. This provider
 * therefore genuinely attempts a compliant fetch and reuses the same
 * structured-metadata extraction as the generic provider. In real testing,
 * Copart's pages were client-rendered with no JSON-LD/OpenGraph data, so
 * this will often return a graceful "failed" result — that's an honest
 * outcome, not a hardcoded refusal, and it will start working automatically
 * if Copart ever server-renders more.
 */
class CopartListingProvider extends AbstractMetadataListingProvider
{
    private const HOSTS = ['copart.co.uk', 'copart.com'];

    /**
     * Path prefixes Copart's own robots.txt disallows for every user-agent.
     * A pasted URL under one of these is declined without ever fetching it.
     */
    private const DISALLOWED_PATH_PREFIXES = [
        '/public/data/', '/paymentsdue/', '/paymenthistory/', '/mybids/',
        '/lotswon/', '/lotslost', '/driverseat/', '/dashboard',
        '/downloadsalesdata', '/memberfees', '/messagesettings',
        '/accountinformation/', '/hireabroker', '/lotsearchresults/',
    ];

    public function supports(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach (self::HOSTS as $suffix) {
            if ($host === $suffix || $host === 'www.'.$suffix || str_ends_with($host, '.'.$suffix)) {
                return true;
            }
        }

        return false;
    }

    public function import(string $url): ListingImportResult
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));

        foreach (self::DISALLOWED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return ListingImportResult::blocked(
                    'copart',
                    "This Copart page is under a path Copart's robots.txt disallows for automated access — please continue manually."
                );
            }
        }

        return $this->importFromUrl($url, 'copart');
    }
}
