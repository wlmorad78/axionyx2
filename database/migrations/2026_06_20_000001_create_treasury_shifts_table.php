<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->string('shift_no', 50)->unique();
            $table->foreignId('cashier_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime')->nullable();
            $table->decimal('opening_balance', 14, 4)->default(0);
            $table->decimal('closing_balance', 14, 4)->default(0);
            $table->decimal('actual_balance', 14, 4)->default(0);
            $table->decimal('difference_amount', 14, 4)->default(0);
            $table->enum('status', ['OPEN', 'PENDING_APPROVAL', 'CLOSED', 'CANCELLED'])->default('OPEN');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_shifts'); }
};
