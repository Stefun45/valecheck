<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the single new endpoint row this feature adds - follows the same
 * pattern as 2026_09_16_120115_create_provider_endpoint_costs_table.php,
 * but only inserts the one new row rather than touching the table itself.
 *
 * 0.75 is the real One Auto "PrePay" tier rate (75p per chargeable call)
 * for this endpoint at the time it was enabled on this account. Unlike
 * every other row here, this one is only actually charged on a genuine
 * HTTP 200 - see OneAutoSalvageAuctionImageProvider - but the admin
 * cost-editing screen still needs a real rate to apply when it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        // insertOrIgnore, not insert: ProviderEndpointCost::ENDPOINTS is a
        // live list read by 2026_09_16_120115_create_provider_endpoint_costs_table
        // at the time IT runs — on a from-scratch migrate (e.g. in tests),
        // that earlier migration already seeds every key present in
        // ENDPOINTS *now*, including this one, so this migration must not
        // assume the row is still missing by the time it runs. In the real
        // production database, that earlier migration ran long before this
        // endpoint existed, so this is the migration that actually creates
        // the row there.
        DB::table('provider_endpoint_costs')->insertOrIgnore([
            'endpoint' => 'salvageguide/thirdpartyimages',
            'cost_net' => 0.75,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('provider_endpoint_costs')->where('endpoint', 'salvageguide/thirdpartyimages')->delete();
    }
};
