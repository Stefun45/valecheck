<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Real listing URLs routinely exceed the default VARCHAR(255) —
        // Auto Trader in particular carries search/finance-calculator
        // context in the query string that can push a single listing URL
        // past 500 characters on its own.
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->text('listing_url')->nullable()->change();
        });

        Schema::table('listing_imports', function (Blueprint $table) {
            $table->text('url')->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->string('listing_url')->nullable()->change();
        });

        Schema::table('listing_imports', function (Blueprint $table) {
            $table->string('url')->change();
        });
    }
};
