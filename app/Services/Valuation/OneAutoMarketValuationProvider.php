<?php

namespace App\Services\Valuation;

use App\DataTransferObjects\MarketValuation;
use App\DataTransferObjects\VehicleData;
use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\OneAuto\OneAutoApiException;
use App\Services\OneAuto\OneAutoClient;

/**
 * Routes to one of two genuinely different One Auto valuation products
 * depending on write-off status — a generic "clean" valuation product has
 * no way to price in damage/write-off history, so a written-off vehicle
 * needs a purpose-built product instead of a flat percentage guess applied
 * to a clean-car price:
 *
 * - Clean vehicles: UK Vehicle Data "Valuation from VRM" — a full
 *   valuation ladder (dealer forecourt, trade, private, part-exchange,
 *   auction). Cheaper than Brego (which this replaces) and returns more
 *   usable detail.
 * - Written-off vehicles: SalvageGuide "Bid Prediction from VRM" — a real
 *   market-calibrated category-adjusted retail range plus a salvage
 *   auction predicted bid range, from actual auction data rather than an
 *   assumed percentage.
 *
 * salvage_category is passed our own already-detected write-off category
 * letter directly (confirmed valid values: A, B, C, D, N, S, U, X — an
 * exact match for the standard UK insurance write-off category scheme,
 * including the pre-2017 C/D categories, so no separate mapping table is
 * needed). primary_damage_desc is the first entry from AutoCheck's
 * damage_location_items, space-separated from its "FrontNearside" style
 * into "Front Nearside" — not a confirmed exact format, since we don't
 * have a live example of this endpoint's real behaviour yet.
 *
 * Needs current_mileage, which isn't collected from the customer any more
 * (removed from the Plus form as unused before the original VehicleMatic
 * migration) — derived instead from the most recent MOT test's odometer
 * reading. A missing/unavailable valuation degrades this section to
 * "unavailable" rather than failing the whole Plus report, since
 * valuation is additive on top of the safety-critical provenance check.
 */
class OneAutoMarketValuationProvider implements MarketValuationProvider
{
    private const CLEAN_ENDPOINT = 'ukvehicledata/valuationfromvrm/v2';

    private const SALVAGE_ENDPOINT = 'salvageguide/bidpredictionfromvrm';

    public function __construct(private readonly OneAutoClient $client) {}

    public function getValuation(VehicleData $vehicle): MarketValuation
    {
        $mileage = $this->latestMileage($vehicle);

        if ($mileage === null) {
            return $this->unavailable();
        }

        return $vehicle->isWrittenOff()
            ? $this->getSalvageValuation($vehicle, $mileage)
            : $this->getCleanValuation($vehicle, $mileage);
    }

    private function getCleanValuation(VehicleData $vehicle, int $mileage): MarketValuation
    {
        try {
            $result = $this->client->get(self::CLEAN_ENDPOINT, $vehicle->registration, [
                'vehicle_registration_mark' => MotHistoryAndTaxStatusFetcher::normalise($vehicle->registration),
                'current_mileage' => $mileage,
            ]);
        } catch (OneAutoApiException) {
            return $this->unavailable();
        }

        $data = $result['valuation_data'] ?? [];

        if (! isset($data['dealer_forecourt'])) {
            return $this->unavailable();
        }

        return new MarketValuation(
            cleanValue: (float) $data['dealer_forecourt'],
            tradeValue: isset($data['trade_retail']) ? (float) $data['trade_retail'] : null,
            retailValue: (float) $data['dealer_forecourt'],
            privateValue: isset($data['private_clean']) ? (float) $data['private_clean'] : null,
            comparables: [],
            confidence: 'medium',
            source: 'ukvehicledata',
            dealerForecourt: (float) $data['dealer_forecourt'],
            tradeAverage: isset($data['trade_average']) ? (float) $data['trade_average'] : null,
            tradePoor: isset($data['trade_poor']) ? (float) $data['trade_poor'] : null,
            privateAverage: isset($data['private_average']) ? (float) $data['private_average'] : null,
            partExchange: isset($data['part_exchange']) ? (float) $data['part_exchange'] : null,
            auctionValue: isset($data['auction_value']) ? (float) $data['auction_value'] : null,
            listPrice: isset($data['list_price_inc_delivery_vat']) ? (float) $data['list_price_inc_delivery_vat'] : null,
        );
    }

    private function getSalvageValuation(VehicleData $vehicle, int $mileage): MarketValuation
    {
        try {
            $result = $this->client->get(self::SALVAGE_ENDPOINT, $vehicle->registration, array_filter([
                'vehicle_registration_mark' => MotHistoryAndTaxStatusFetcher::normalise($vehicle->registration),
                'salvage_category' => $vehicle->writeOffCategory,
                'primary_damage_desc' => $this->primaryDamageDescription($vehicle),
                'current_mileage' => $mileage,
            ]));
        } catch (OneAutoApiException) {
            return $this->unavailable();
        }

        if (! isset($result['category_adjusted_retail_value_low_gbp'], $result['category_adjusted_retail_value_high_gbp'])) {
            return $this->unavailable();
        }

        $low = (float) $result['category_adjusted_retail_value_low_gbp'];
        $high = (float) $result['category_adjusted_retail_value_high_gbp'];

        return new MarketValuation(
            cleanValue: null, // SalvageGuide doesn't return an undamaged baseline.
            tradeValue: null,
            retailValue: null,
            privateValue: null,
            comparables: [],
            confidence: 'medium',
            source: 'salvageguide',
            categoryAdjustedLow: $low,
            categoryAdjustedHigh: $high,
            salvageAuctionBidLow: isset($result['salvage_auction_predicted_bid_low_gbp']) ? (float) $result['salvage_auction_predicted_bid_low_gbp'] : null,
            salvageAuctionBidAverage: isset($result['salvage_auction_predicted_bid_average_gbp']) ? (float) $result['salvage_auction_predicted_bid_average_gbp'] : null,
            salvageAuctionBidHigh: isset($result['salvage_auction_predicted_bid_high_gbp']) ? (float) $result['salvage_auction_predicted_bid_high_gbp'] : null,
        );
    }

    private function primaryDamageDescription(VehicleData $vehicle): ?string
    {
        $raw = $vehicle->damageLocations[0] ?? null;

        if ($raw === null) {
            return null;
        }

        return trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $raw));
    }

    private function unavailable(): MarketValuation
    {
        return new MarketValuation(null, null, null, null, [], 'unavailable');
    }

    private function latestMileage(VehicleData $vehicle): ?int
    {
        $latest = collect($vehicle->motHistory)
            ->filter(fn (array $test) => isset($test['mileage'], $test['test_date']))
            ->sortByDesc('test_date')
            ->first();

        return $latest['mileage'] ?? null;
    }
}
