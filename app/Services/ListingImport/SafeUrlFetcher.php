<?php

namespace App\Services\ListingImport;

use App\DataTransferObjects\FetchedPage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Fetches a URL supplied by a customer (untrusted input) while blocking
 * SSRF targets: non-http(s) schemes, localhost, private/loopback/link-local
 * ranges (including cloud metadata addresses), at every redirect hop — not
 * just the initial URL. Always goes through the Http facade so tests can
 * intercept it with Http::fake()/Http::preventStrayRequests() exactly like
 * every other outbound provider call in this codebase.
 */
class SafeUrlFetcher
{
    public function fetch(string $url): FetchedPage
    {
        $maxRedirects = (int) config('valecheck.listing_import.max_redirects', 5);
        $redirects = 0;

        while (true) {
            $this->assertFetchable($url);

            $response = $this->attemptRequest($url);
            $status = $response->status();

            if ($status >= 300 && $status < 400 && $redirects < $maxRedirects) {
                $location = $response->header('Location');

                if (! $location) {
                    throw new SsrfBlockedException("Redirect from {$url} had no Location header.");
                }

                $url = $this->resolveRedirectUrl($url, $location);
                $redirects++;

                continue;
            }

            return new FetchedPage(
                finalUrl: $url,
                statusCode: $status,
                body: $this->truncateToMaxSize($response->body()),
                contentType: $response->header('Content-Type'),
            );
        }
    }

    /**
     * Downloads binary content (e.g. an image) with the same SSRF/redirect
     * protections, returning the raw bytes or null on any failure.
     */
    public function fetchBinary(string $url): ?string
    {
        try {
            $page = $this->fetch($url);
        } catch (SsrfBlockedException) {
            return null;
        }

        return $page->statusCode >= 200 && $page->statusCode < 300 ? $page->body : null;
    }

    private function assertFetchable(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new SsrfBlockedException("Blocked scheme for listing import: {$scheme}");
        }

        $host = strtolower($parts['host'] ?? '');

        if ($host === '' || $host === 'localhost') {
            throw new SsrfBlockedException("Blocked host for listing import: {$host}");
        }

        $ips = $this->resolveIps($host);

        if ($ips === []) {
            throw new SsrfBlockedException("Could not resolve host for listing import: {$host}");
        }

        foreach ($ips as $ip) {
            if ($this->isDisallowedIp($ip)) {
                throw new SsrfBlockedException("Blocked destination IP for listing import: {$ip} (host {$host})");
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);

        if ($records === false) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (array $record) => $record['ip'] ?? $record['ipv6'] ?? null,
            $records
        )));
    }

    private function isDisallowedIp(string $ip): bool
    {
        // Rejects private (RFC1918/ULA) and reserved ranges in one call —
        // "reserved" covers loopback (127.0.0.0/8, ::1) and link-local
        // (169.254.0.0/16, fe80::/10), which includes cloud metadata
        // addresses such as 169.254.169.254.
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function resolveRedirectUrl(string $from, string $location): string
    {
        if (parse_url($location, PHP_URL_SCHEME)) {
            return $location;
        }

        $base = parse_url($from);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        $path = $base['path'] ?? '/';
        $dir = rtrim(substr($path, 0, (int) strrpos($path, '/') + 1), '/');

        return "{$scheme}://{$host}{$port}{$dir}/{$location}";
    }

    private function attemptRequest(string $url): Response
    {
        for ($attempt = 0; ; $attempt++) {
            try {
                return Http::connectTimeout((int) config('valecheck.listing_import.connect_timeout', 5))
                    ->timeout((int) config('valecheck.listing_import.request_timeout', 15))
                    ->withOptions(['allow_redirects' => false])
                    ->withHeaders(['User-Agent' => (string) config('valecheck.listing_import.user_agent')])
                    ->get($url);
            } catch (ConnectionException $e) {
                // Only transient network failures are retried, and only
                // once — a deliberate block (403/429/etc.) is a normal HTTP
                // response, not an exception, and must never be retried.
                if ($attempt >= 1) {
                    throw $e;
                }

                usleep(300_000);
            }
        }
    }

    private function truncateToMaxSize(string $body): string
    {
        $maxBytes = ((int) config('valecheck.listing_import.max_page_size_kb', 5120)) * 1024;

        return strlen($body) > $maxBytes ? substr($body, 0, $maxBytes) : $body;
    }
}
