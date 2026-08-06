<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_incentive_id')->constrained('sales_incentives');
            $table->integer('priority')->default(0);
            $table->boolean('allow_combination')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_priorities');
    }
};
