<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('agreement_no', 50);
            $table->foreignId('agreement_type_id')->constrained('customer_agreement_types');
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('agreement_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['DRAFT', 'PENDING', 'ACTIVE', 'EXPIRED', 'CANCELLED'])->default('DRAFT');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'agreement_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_agreements');
    }
};
