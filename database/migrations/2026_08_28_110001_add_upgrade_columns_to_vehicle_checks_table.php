<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            // Kept separate from payment_id (which always stays the payment
            // that originally created this check) so a Check-to-Plus upgrade
            // never overwrites the original purchase's audit trail.
            $table->foreignId('upgrade_payment_id')->nullable()->after('payment_id')->constrained('payments')->nullOnDelete();
            $table->timestamp('upgraded_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upgrade_payment_id');
            $table->dropColumn('upgraded_at');
        });
    }
};
