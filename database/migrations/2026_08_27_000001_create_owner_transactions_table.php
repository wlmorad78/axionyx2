<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->constrained();
            $table->string('transaction_type')->comment('cash_deposit, cash_withdrawal, goods_dispatch, goods_receive');
            $table->decimal('amount', 15, 2)->nullable()->comment('المبلغ للعمليات النقدية');
            $table->foreignId('item_id')->nullable()->constrained('items')->comment('الصنف لعمليات البضاعة');
            $table->decimal('quantity', 15, 2)->nullable()->comment('الكمية لعمليات البضاعة');
            $table->decimal('unit_cost', 15, 2)->nullable()->comment('تكلفة الوحدة');
            $table->decimal('total_cost', 15, 2)->nullable()->comment('التكلفة الإجمالية');
            $table->foreignId('treasury_id')->nullable()->constrained()->comment('الخزينة للعمليات النقدية');
            $table->foreignId('warehouse_id')->nullable()->constrained()->comment('المخزن لعمليات البضاعة');
            $table->string('reference_type')->nullable()->comment('نوع المستند المرجعي');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('معرف المستند المرجعي');
            $table->string('description')->nullable();
            $table->date('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('employees');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_transactions');
    }
};
