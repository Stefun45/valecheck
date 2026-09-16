<?php

use App\Models\ProviderEndpointCost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_endpoint_costs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint')->unique();
            $table->decimal('cost_net', 8, 4);
            $table->timestamps();
        });

        // Seeded from the previous single flat cost_per_lookup_net figure,
        // so this migration doesn't change what any report is currently
        // costed at — the new admin screen is what sets real per-endpoint
        // costs from here on.
        $now = now();
        $legacyFlatCost = (float) config('valecheck.vehicle_data.oneauto.cost_per_lookup_net', 0);

        DB::table('provider_endpoint_costs')->insert(
            collect(array_keys(ProviderEndpointCost::ENDPOINTS))
                ->map(fn (string $endpoint) => [
                    'endpoint' => $endpoint,
                    'cost_net' => $legacyFlatCost,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_endpoint_costs');
    }
};
