<?php

namespace App\Services\ListingImport;

use App\DataTransferObjects\ListingImportResult;

class ListingImportService
{
    /**
     * @param  array<int, ListingImportProvider>  $providers  Checked in order — the generic
     *                                                        provider must be registered last as the catch-all.
     */
    public function __construct(private readonly array $providers) {}

    public function import(string $url): ListingImportResult
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($url)) {
                return $provider->import($url);
            }
        }

        return ListingImportResult::failed('none', 'No import provider supports this URL.');
    }
}
