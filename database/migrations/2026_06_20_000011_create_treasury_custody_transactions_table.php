<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_custody_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_custody_id')->constrained('treasury_custodies')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->enum('transaction_type', ['ISSUE', 'RETURN', 'SETTLEMENT', 'ADJUSTMENT']);
            $table->decimal('amount', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_custody_transactions'); }
};
