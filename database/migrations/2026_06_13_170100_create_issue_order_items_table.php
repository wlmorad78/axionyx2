<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('issue_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_order_id')->constrained('issue_orders');
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('item_unit_id')->nullable()->constrained('item_units');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->decimal('requested_quantity', 12, 2)->default(0);
            $table->decimal('issued_quantity', 12, 2)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sales_price', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('issue_order_items');
    }
};