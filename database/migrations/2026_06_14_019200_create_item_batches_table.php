<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained();
            $table->string('batch_no', 50);
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('purchase_price', 15, 4);
            $table->decimal('qty', 15, 2);
            $table->decimal('remaining_qty', 15, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('item_batches'); }
};
