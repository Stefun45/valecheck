<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan'); // trader, pro, dealer
            // Which product this allowance window applies to. A single plan
            // could grant windows for more than one report type in future —
            // never assume "subscription usage" means one hard-coded product.
            $table->string('report_type')->default('rebuild');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('allowance')->nullable(); // null = unlimited, fair use applies
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usages');
    }
};
