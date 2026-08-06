<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_shift_id')->constrained('treasury_shifts')->cascadeOnDelete();
            $table->string('count_no', 50)->unique();
            $table->date('count_date');
            $table->foreignId('counted_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('expected_amount', 14, 4)->default(0);
            $table->decimal('actual_amount', 14, 4)->default(0);
            $table->decimal('difference_amount', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_counts'); }
};
