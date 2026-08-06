<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoice_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices');
            $table->foreignId('provider_id')->constrained('e_invoice_providers');
            $table->string('external_reference')->nullable();
            $table->enum('status', ['pending', 'sent', 'accepted', 'rejected'])->default('pending');
            $table->dateTime('submitted_at')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoice_transactions');
    }
};
