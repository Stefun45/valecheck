<?php

namespace App\DataTransferObjects;

/**
 * Result of ListingImportProvider::import(). `fields` is deliberately a flat
 * associative array (field => ['value' => mixed, 'found' => bool]) rather
 * than a rigid typed DTO with dozens of nullable properties — the set of
 * fields a listing can plausibly expose varies a lot by provider, and the
 * "found" flag is what the preview UI and source-tracking actually need.
 */
final readonly class ListingImportResult
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BLOCKED = 'blocked';

    /**
     * @param  array<string, array{value: mixed, found: bool}>  $fields
     * @param  array<int, ListingImportImage>  $images
     */
    public function __construct(
        public string $status,
        public string $provider,
        public array $fields = [],
        public array $images = [],
        public ?int $httpStatus = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(string $provider, array $fields, array $images = [], ?int $httpStatus = null): self
    {
        return new self(self::STATUS_SUCCESS, $provider, $fields, $images, $httpStatus);
    }

    public static function partial(string $provider, array $fields, array $images = [], ?int $httpStatus = null): self
    {
        return new self(self::STATUS_PARTIAL, $provider, $fields, $images, $httpStatus);
    }

    public static function failed(string $provider, string $message, ?int $httpStatus = null): self
    {
        return new self(self::STATUS_FAILED, $provider, errorMessage: $message, httpStatus: $httpStatus);
    }

    public static function blocked(string $provider, string $message): self
    {
        return new self(self::STATUS_BLOCKED, $provider, errorMessage: $message);
    }

    public function fieldsFoundCount(): int
    {
        return count(array_filter($this->fields, fn (array $f) => $f['found'] === true));
    }
}
