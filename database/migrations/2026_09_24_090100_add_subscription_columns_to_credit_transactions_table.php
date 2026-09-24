<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            // Null for every existing credit type (free grants, purchases,
            // consumption, refunds never expire) - only a subscription
            // monthly allocation sets this, to the end of the billing
            // period it was granted for. CreditLedgerService::balance()
            // excludes expired rows, which is what makes "no rollover"
            // happen automatically with no separate void step.
            $table->timestamp('expires_at')->nullable()->after('amount');
            $table->foreignId('subscription_plan_id')->nullable()->after('expires_at')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_plan_id');
            $table->dropColumn('expires_at');
        });
    }
};
