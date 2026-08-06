<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_incentive_id')->constrained('sales_incentives');
            $table->foreignId('excluded_incentive_id')->constrained('sales_incentives');
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_exclusions');
    }
};
