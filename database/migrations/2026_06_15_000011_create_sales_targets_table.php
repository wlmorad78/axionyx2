<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('employees');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('target_amount', 12, 2);
            $table->integer('target_customers')->default(0);
            $table->integer('target_visits')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
