<?php

namespace App\Services\ListingImport;

use App\DataTransferObjects\ListingImportImage;
use App\DataTransferObjects\ListingImportResult;
use DOMDocument;
use DOMXPath;

/**
 * Shared structured-metadata extraction (JSON-LD, OpenGraph, plain <title>/
 * meta description fallback) used by any provider that fetches a page and
 * hopes it was server-rendered with genuine metadata, rather than guessing
 * at fragile, site-specific CSS selectors.
 */
abstract class AbstractMetadataListingProvider implements ListingImportProvider
{
    private const FIELD_KEYS = [
        'title', 'description', 'registration', 'vin', 'make', 'model', 'derivative',
        'year', 'engine', 'fuel_type', 'transmission', 'mileage', 'colour',
        'asking_price', 'current_bid', 'buy_it_now_price', 'auction_end_time',
        'seller_condition_notes', 'location', 'listing_ref',
    ];

    public function __construct(protected SafeUrlFetcher $fetcher) {}

    protected function importFromUrl(string $url, string $providerName): ListingImportResult
    {
        try {
            $page = $this->fetcher->fetch($url);
        } catch (SsrfBlockedException $e) {
            return ListingImportResult::blocked($providerName, $e->getMessage());
        }

        if ($page->statusCode < 200 || $page->statusCode >= 300) {
            return ListingImportResult::failed(
                $providerName,
                "The listing page returned HTTP {$page->statusCode}.",
                $page->statusCode
            );
        }

        $document = $this->parseHtml($page->body);

        $jsonLd = $this->extractFromJsonLd($document);
        $openGraph = $this->extractFromOpenGraph($document);
        $fallback = $this->extractFallback($document);

        $values = array_merge($fallback, $openGraph, $jsonLd['fields']);
        $imageUrls = array_values(array_unique(array_merge($jsonLd['images'], $this->extractOpenGraphImages($document))));

        $fields = [];
        foreach (self::FIELD_KEYS as $key) {
            $value = $values[$key] ?? null;
            $fields[$key] = ['value' => $value, 'found' => $value !== null && $value !== ''];
        }

        $images = [];
        foreach ($imageUrls as $index => $imageUrl) {
            $images[] = new ListingImportImage($imageUrl, $index);
        }

        $foundCount = count(array_filter($fields, fn (array $f) => $f['found']));

        if ($foundCount === 0 && $images === []) {
            return ListingImportResult::failed(
                $providerName,
                'No structured listing data (JSON-LD, OpenGraph or page metadata) was found on this page.',
                $page->statusCode
            );
        }

        return $foundCount >= 3
            ? ListingImportResult::success($providerName, $fields, $images, $page->statusCode)
            : ListingImportResult::partial($providerName, $fields, $images, $page->statusCode);
    }

    private function parseHtml(string $html): DOMDocument
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        return $document;
    }

    /**
     * @return array{fields: array<string, mixed>, images: array<int, string>}
     */
    private function extractFromJsonLd(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//script[@type="application/ld+json"]');

        $fields = [];
        $images = [];

        foreach ($nodes ?: [] as $node) {
            $decoded = json_decode($node->textContent, true);

            if (! is_array($decoded)) {
                continue;
            }

            $items = $decoded['@graph'] ?? (array_is_list($decoded) ? $decoded : [$decoded]);

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $types = (array) ($item['@type'] ?? []);

                if (array_intersect($types, ['Organization', 'WebSite', 'BreadcrumbList', 'WebPage', 'SiteNavigationElement'])) {
                    continue;
                }

                $fields = array_merge($fields, $this->mapJsonLdItem($item));
                $images = array_merge($images, $this->jsonLdImages($item));
            }
        }

        return ['fields' => $fields, 'images' => $images];
    }

    private function mapJsonLdItem(array $item): array
    {
        $out = [];

        if (isset($item['name'])) {
            $out['title'] = (string) $item['name'];
        }

        if (isset($item['description'])) {
            $out['description'] = (string) $item['description'];
        }

        if (isset($item['vehicleIdentificationNumber'])) {
            $out['vin'] = (string) $item['vehicleIdentificationNumber'];
        }

        if (isset($item['brand'])) {
            $out['make'] = is_array($item['brand']) ? ($item['brand']['name'] ?? null) : $item['brand'];
        } elseif (isset($item['manufacturer'])) {
            $out['make'] = is_array($item['manufacturer']) ? ($item['manufacturer']['name'] ?? null) : $item['manufacturer'];
        }

        if (isset($item['model'])) {
            $out['model'] = is_array($item['model']) ? ($item['model']['name'] ?? null) : $item['model'];
        }

        $year = $item['vehicleModelDate'] ?? $item['modelDate'] ?? $item['productionDate'] ?? null;

        if ($year !== null) {
            $out['year'] = (string) $year;
        }

        if (isset($item['mileageFromOdometer'])) {
            $mileage = $item['mileageFromOdometer'];
            $out['mileage'] = is_array($mileage) ? ($mileage['value'] ?? null) : $mileage;
        }

        if (isset($item['color'])) {
            $out['colour'] = $item['color'];
        }

        if (isset($item['vehicleTransmission'])) {
            $out['transmission'] = $item['vehicleTransmission'];
        }

        if (isset($item['fuelType'])) {
            $fuel = $item['fuelType'];
            $out['fuel_type'] = is_array($fuel) ? ($fuel[0] ?? null) : $fuel;
        }

        if (isset($item['vehicleEngine'])) {
            $engine = $item['vehicleEngine'];
            $out['engine'] = is_array($engine)
                ? ($engine['name'] ?? (($engine['engineDisplacement']['value'] ?? null)))
                : $engine;
        }

        $ref = $item['sku'] ?? $item['productID'] ?? null;

        if ($ref !== null) {
            $out['listing_ref'] = (string) $ref;
        }

        if (isset($item['offers'])) {
            $offers = $item['offers'];
            $offer = array_is_list($offers) ? ($offers[0] ?? []) : $offers;

            if (is_array($offer)) {
                if (isset($offer['price'])) {
                    $out['asking_price'] = (float) $offer['price'];
                }

                $end = $offer['priceValidUntil'] ?? $offer['availabilityEnds'] ?? null;

                if ($end !== null) {
                    $out['auction_end_time'] = $end;
                }
            }
        }

        if (isset($item['itemLocation'])) {
            $location = $item['itemLocation'];
            $out['location'] = is_array($location)
                ? ($location['name'] ?? ($location['address']['addressLocality'] ?? null))
                : $location;
        }

        return array_filter($out, fn ($value) => $value !== null && $value !== '');
    }

    private function jsonLdImages(array $item): array
    {
        $image = $item['image'] ?? null;

        if (is_string($image)) {
            return [$image];
        }

        if (is_array($image)) {
            return array_values(array_filter(array_map(
                fn ($entry) => is_string($entry) ? $entry : ($entry['url'] ?? null),
                array_is_list($image) ? $image : [$image]
            )));
        }

        return [];
    }

    private function extractFromOpenGraph(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);

        $read = fn (string $property) => $xpath->query("//meta[@property='{$property}']/@content")->item(0)?->nodeValue;

        $out = [
            'title' => $read('og:title'),
            'description' => $read('og:description'),
            'asking_price' => $read('product:price:amount'),
        ];

        if ($out['asking_price'] !== null) {
            $out['asking_price'] = (float) $out['asking_price'];
        }

        return array_filter($out, fn ($value) => $value !== null && $value !== '');
    }

    private function extractOpenGraphImages(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query("//meta[@property='og:image']/@content");

        $images = [];

        foreach ($nodes ?: [] as $node) {
            $images[] = $node->nodeValue;
        }

        return $images;
    }

    private function extractFallback(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);
        $out = [];

        $title = $xpath->query('//title')->item(0);

        if ($title && trim($title->textContent) !== '') {
            $out['title'] = trim($title->textContent);
        }

        $description = $xpath->query("//meta[@name='description']/@content")->item(0);

        if ($description && trim((string) $description->nodeValue) !== '') {
            $out['description'] = trim($description->nodeValue);
        }

        return $out;
    }
}
