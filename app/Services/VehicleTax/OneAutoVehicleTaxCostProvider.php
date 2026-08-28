<?php

namespace App\Services\VehicleTax;

use App\DataTransferObjects\VehicleTaxCostData;
use App\Services\OneAuto\OneAutoApiException;
use App\Services\OneAuto\OneAutoClient;

/**
 * One Auto API — "Vehicle Tax from VRM" (oneauto/vehicletaxfromvrm/v2).
 *
 * Confirmed against a real sandbox response — no six-month rate field
 * exists in the API's response at all, only the annual rate(s), so the
 * six-month figure is derived ourselves. DVLA's own published "Single
 * 6 month payment" rate is 55% of the annual rate (confirmed against a
 * real DVLA-sourced example: £195 annual → £107.25 six month).
 *
 * list_price_inc_options_delivery_vat is a required parameter that
 * determines whether the >£40,000 list price "premium" supplement
 * applies (years 2-6 of ownership) — we don't have this vehicle's
 * original list price anywhere in our data, so it's passed as 0, which
 * means the premium supplement is never spuriously applied. This slightly
 * understates the true annual cost for the minority of vehicles that were
 * genuinely over £40,000 when new and are between their 2nd and 6th
 * registration anniversary.
 *
 * A failed/unavailable check degrades to "unavailable" rather than
 * failing the whole Plus report — this is additive, not safety-critical.
 */
class OneAutoVehicleTaxCostProvider implements VehicleTaxCostProvider
{
    private const ENDPOINT = 'oneauto/vehicletaxfromvrm/v2';

    private const SIX_MONTH_RATE_FACTOR = 0.55;

    public function __construct(private readonly OneAutoClient $client) {}

    public function check(string $registration, ?int $vehicleCheckId = null): VehicleTaxCostData
    {
        try {
            $result = $this->client->get(self::ENDPOINT, $registration, [
                'vehicle_registration_mark' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $registration)),
                'list_price_inc_options_delivery_vat' => 0,
            ], $vehicleCheckId);
        } catch (OneAutoApiException) {
            return new VehicleTaxCostData(available: false);
        }

        $annualRate = $result['12_month_rfl_premium'] ?? $result['12_month_rfl_y1'] ?? $result['12_month_rfl'] ?? null;

        if ($annualRate === null) {
            return new VehicleTaxCostData(available: false);
        }

        return new VehicleTaxCostData(
            available: true,
            annualRate: (float) $annualRate,
            sixMonthRate: round($annualRate * self::SIX_MONTH_RATE_FACTOR, 2),
            // type_approval_category ('M1' etc.) is a vehicle-type-approval
            // code, not a real VED tax band — post-2017 cars like this one
            // don't have the old A-M letter bands at all, so there's
            // nothing meaningful to put here. Left null deliberately.
            taxClass: null,
        );
    }
}
