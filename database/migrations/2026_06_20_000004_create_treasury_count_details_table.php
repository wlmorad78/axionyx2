<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_count_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_count_id')->constrained('treasury_counts')->cascadeOnDelete();
            $table->string('denomination');
            $table->integer('qty')->default(0);
            $table->decimal('total_amount', 14, 4)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_count_details'); }
};
