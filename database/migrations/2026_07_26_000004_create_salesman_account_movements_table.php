<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salesman_account_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('salesman_account_id')->constrained('salesman_accounts');
            $table->foreignId('salesman_id')->constrained('employees');
            $table->date('movement_date');
            $table->string('movement_type', 50)->comment('sale, return_approved, collection, debt_creation, debt_payment, adjustment, writeoff, closure');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('document_no', 50)->nullable()->comment('رقم المستند المرتبط');
            $table->decimal('debit', 15, 2)->default(0)->comment('مدين - تحمّل المندوب');
            $table->decimal('credit', 15, 2)->default(0)->comment('دائن - ما سدّده المندوب');
            $table->decimal('balance', 15, 2)->default(0)->comment('الرصيد بعد الحركة');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesman_account_movements');
    }
};