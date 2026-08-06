<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_cash_account_id')->constrained('vehicle_cash_accounts')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->enum('transaction_type', ['COLLECTION', 'EXPENSE', 'DEPOSIT', 'SETTLEMENT']);
            $table->decimal('amount', 14, 4)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_cash_transactions'); }
};
