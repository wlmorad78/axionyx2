<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries');
            $table->foreignId('expense_type_id')->nullable()->constrained('expense_types');
            $table->string('code', 50);
            $table->date('expense_date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payee_name')->nullable();
            $table->text('description')->nullable();
            $table->string('payment_method')->default('cash');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
