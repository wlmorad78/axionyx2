<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tax_group_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_group_id')->constrained('tax_groups')->cascadeOnDelete();
            $table->foreignId('tax_type_id')->constrained('tax_types')->cascadeOnDelete();
            $table->integer('calculation_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tax_group_details'); }
};
