<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->string('adjustment_no', 50)->unique();
            $table->date('adjustment_date');
            $table->enum('adjustment_type', ['SHORTAGE', 'OVERAGE', 'CORRECTION']);
            $table->decimal('amount', 14, 4)->default(0);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_adjustments'); }
};
