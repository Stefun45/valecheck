<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set when a downgrade is requested mid-period - applied (the
            // Stripe subscription swapped, new credits granted) at the next
            // renewal webhook, never immediately. Null means no downgrade
            // is pending.
            $table->foreignId('pending_plan_id')->nullable()->after('is_admin')->constrained('subscription_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pending_plan_id');
        });
    }
};
