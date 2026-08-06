<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('return_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_order_id')->constrained('return_orders');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_unit_id')->nullable()->constrained('item_units');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->decimal('returned_quantity', 12, 2)->default(0);
            $table->decimal('sales_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedBigInteger('return_reason_id')->nullable();
            $table->string('return_condition', 50)->default('damaged'); // damaged, expired, wrong_item, excess, quality_issue, good
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('return_order_items');
    }
};
