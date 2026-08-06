<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_agreement_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_agreement_id')->constrained('customer_agreements');
            $table->date('payment_date');
            $table->enum('payment_type', ['CASH', 'DISCOUNT', 'CREDIT_NOTE', 'FREE_GOODS']);
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_agreement_payments');
    }
};
