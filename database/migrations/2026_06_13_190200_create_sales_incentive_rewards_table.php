<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales_incentive_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_incentive_id')->constrained('sales_incentives');
            $table->string('reward_type', 50); // cash, percentage, free_product, points
            $table->decimal('reward_value', 12, 2)->default(0);
            $table->decimal('max_reward', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sales_incentive_rewards');
    }
};
