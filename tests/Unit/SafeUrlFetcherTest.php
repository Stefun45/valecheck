<?php

namespace Tests\Unit;

use App\Services\ListingImport\SafeUrlFetcher;
use App\Services\ListingImport\SsrfBlockedException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeUrlFetcherTest extends TestCase
{
    public static function blockedUrlProvider(): array
    {
        return [
            'localhost hostname' => ['http://localhost/lot/1'],
            'loopback IPv4' => ['http://127.0.0.1/lot/1'],
            'loopback IPv6' => ['http://[::1]/lot/1'],
            'private RFC1918 10/8' => ['http://10.0.0.5/lot/1'],
            'private RFC1918 172.16/12' => ['http://172.16.5.5/lot/1'],
            'private RFC1918 192.168/16' => ['http://192.168.1.1/lot/1'],
            'link-local / cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/file'],
        ];
    }

    #[DataProvider('blockedUrlProvider')]
    public function test_blocked_destinations_are_rejected_before_any_request_is_sent(string $url): void
    {
        Http::fake();

        try {
            (new SafeUrlFetcher)->fetch($url);
            $this->fail('Expected an SsrfBlockedException to be thrown.');
        } catch (SsrfBlockedException) {
            // expected
        }

        Http::assertNothingSent();
    }

    public function test_a_legitimate_public_url_is_fetched_successfully(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><title>Test</title></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $page = (new SafeUrlFetcher)->fetch('https://example.com/listing/1');

        $this->assertSame(200, $page->statusCode);
        $this->assertStringContainsString('Test', $page->body);
    }

    public function test_a_redirect_to_a_private_ip_is_blocked_rather_than_followed(): void
    {
        Http::fake([
            'example.com/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
        ]);

        $this->expectException(SsrfBlockedException::class);

        (new SafeUrlFetcher)->fetch('https://example.com/redirect-me');
    }

    public function test_a_redirect_to_another_public_path_is_followed(): void
    {
        // Redirects within the same real, resolvable host — dns_get_record()
        // performs genuine DNS resolution (not intercepted by Http::fake),
        // so the redirect target must be a real, publicly resolvable domain.
        Http::fake([
            'example.com/redirect-me' => Http::response('', 302, ['Location' => 'https://example.com/final']),
            'example.com/final' => Http::response('<html><title>Final</title></html>', 200),
        ]);

        $page = (new SafeUrlFetcher)->fetch('https://example.com/redirect-me');

        $this->assertSame('https://example.com/final', $page->finalUrl);
        $this->assertStringContainsString('Final', $page->body);
    }
}
