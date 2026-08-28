<?php

namespace App\DataTransferObjects;

/**
 * Internal representation of a salvage auction history check — has this
 * vehicle previously been listed (and photographed) at a salvage auction.
 * Every SalvageAuctionProvider must translate its own response shape into
 * this DTO.
 */
final readonly class SalvageAuctionData
{
    /**
     * @param  array<int, array{
     *     lotDescription: ?string,
     *     lotDate: ?string,
     *     mileage: ?int,
     *     primaryDamageDescription: ?string,
     *     secondaryDamageDescription: ?string,
     *     location: ?string,
     *     imageUrls: array<int, string>,
     * }>  $records
     */
    public function __construct(
        public bool $recordFound,
        public array $records,
    ) {}
}
