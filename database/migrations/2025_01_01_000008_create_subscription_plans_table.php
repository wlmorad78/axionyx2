<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->string('name');

            $table->unsignedInteger('duration_months');

            $table->decimal('price', 12, 2);

            $table->unsignedInteger('max_branches')->default(1);

            $table->unsignedInteger('max_warehouses')->default(1);

            $table->unsignedInteger('max_treasuries')->default(1);

            $table->unsignedTinyInteger('grace_period_days')->default(5);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
