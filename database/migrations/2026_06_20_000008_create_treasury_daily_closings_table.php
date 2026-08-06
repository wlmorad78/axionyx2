<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_daily_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->date('closing_date');
            $table->decimal('opening_balance', 14, 4)->default(0);
            $table->decimal('receipts_total', 14, 4)->default(0);
            $table->decimal('payments_total', 14, 4)->default(0);
            $table->decimal('transfers_in', 14, 4)->default(0);
            $table->decimal('transfers_out', 14, 4)->default(0);
            $table->decimal('expected_balance', 14, 4)->default(0);
            $table->decimal('actual_balance', 14, 4)->default(0);
            $table->decimal('difference_amount', 14, 4)->default(0);
            $table->enum('status', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED'])->default('DRAFT');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_daily_closings'); }
};
