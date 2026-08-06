<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('from_treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->foreignId('to_treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->string('transfer_no', 50)->unique();
            $table->date('transfer_date');
            $table->decimal('amount', 14, 4)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['DRAFT', 'POSTED', 'CANCELLED'])->default('DRAFT');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_transfers'); }
};
