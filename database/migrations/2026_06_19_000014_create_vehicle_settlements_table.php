<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('settlement_no', 50)->unique();
            $table->date('settlement_date');
            $table->decimal('opening_stock_value', 14, 4)->default(0);
            $table->decimal('loaded_value', 14, 4)->default(0);
            $table->decimal('sales_value', 14, 4)->default(0);
            $table->decimal('collection_value', 14, 4)->default(0);
            $table->decimal('return_value', 14, 4)->default(0);
            $table->decimal('expense_value', 14, 4)->default(0);
            $table->decimal('closing_stock_value', 14, 4)->default(0);
            $table->decimal('cash_difference', 14, 4)->default(0);
            $table->decimal('stock_difference', 14, 4)->default(0);
            $table->enum('status', ['DRAFT', 'COMPLETED', 'APPROVED'])->default('DRAFT');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_settlements'); }
};
