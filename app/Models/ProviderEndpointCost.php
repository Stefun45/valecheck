<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The real, admin-editable net cost of one call to a specific One Auto
 * endpoint. Deliberately per-endpoint rather than one flat figure — the
 * MOT/tax call, the AutoCheck provenance call, the valuation call, the
 * salvage calls, the tax call, and the two imagery calls are all billed
 * differently, and the plan/rate can change over time (e.g. a cheaper
 * tier for the same endpoint).
 *
 * Editing a row here only affects calls logged from now on —
 * ProviderLookupLog snapshots the cost onto each row at the moment the
 * call happens (see OneAutoClient::log()), the same way Payment snapshots
 * the selling price at checkout, so past reporting is never silently
 * rewritten by a later cost change.
 */
#[Fillable(['endpoint', 'cost_net'])]
class ProviderEndpointCost extends Model
{
    /**
     * Every distinct One Auto endpoint string called anywhere in the app,
     * with a human label for the admin cost-editing screen. Kept as the
     * single source of truth here rather than duplicated in the
     * controller/view/seeding migration.
     *
     * @var array<string, string>
     */
    public const ENDPOINTS = [
        'experian/autocheck/v3' => 'AutoCheck (provenance) — Check + Plus',
        'oneauto/mothistoryandtaxstatus/v2' => 'MOT History & Tax Status — Check + Plus + free preview',
        'ukvehicledata/valuationfromvrm/v2' => 'Valuation (clean vehicles) — Plus',
        'salvageguide/bidpredictionfromvrm' => 'Salvage Bid Prediction (written-off vehicles) — Plus',
        'carguide/salvagecheck/v2' => 'Salvage Auction Check — Plus',
        'oneauto/vehicletaxfromvrm/v2' => 'Vehicle Tax Cost — Plus',
        'vehicleimagery/imagesearchfromvrm' => 'Vehicle Imagery — image search',
        'vehicleimagery/imagefromid' => 'Vehicle Imagery — image fetch',
    ];

    protected function casts(): array
    {
        return [
            'cost_net' => 'decimal:4',
        ];
    }

    /**
     * Endpoint strings contain slashes, which aren't safe as raw HTML form
     * field names — this gives each one a stable, safe field name instead,
     * shared between the admin controller and its edit form.
     */
    public static function fieldName(string $endpoint): string
    {
        return str_replace(['/', '.'], '_', $endpoint);
    }
}
