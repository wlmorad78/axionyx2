<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pricing_calculation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_calculation_id')->constrained('pricing_calculations')->cascadeOnDelete();
            $table->integer('calculation_step')->default(0);
            $table->string('description');
            $table->decimal('amount', 14, 4)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pricing_calculation_details'); }
};
