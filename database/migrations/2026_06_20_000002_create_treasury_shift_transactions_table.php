<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_shift_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_shift_id')->constrained('treasury_shifts')->cascadeOnDelete();
            $table->enum('transaction_type', ['RECEIPT', 'PAYMENT', 'DEPOSIT', 'WITHDRAWAL', 'TRANSFER', 'ADJUSTMENT']);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('amount', 14, 4)->default(0);
            $table->dateTime('transaction_datetime');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_shift_transactions'); }
};
