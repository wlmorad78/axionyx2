<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales_incentive_condition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('condition_id')->constrained('sales_incentive_conditions');
            $table->foreignId('item_id')->constrained('items');
            $table->decimal('required_qty', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('sales_incentive_condition_items');
    }
};
