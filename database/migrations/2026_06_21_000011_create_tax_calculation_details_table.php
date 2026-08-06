<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tax_calculation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_calculation_id')->constrained('tax_calculations')->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->decimal('tax_rate', 6, 2)->default(0);
            $table->decimal('taxable_amount', 14, 4)->default(0);
            $table->decimal('tax_amount', 14, 4)->default(0);
            $table->integer('calculation_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tax_calculation_details'); }
};
