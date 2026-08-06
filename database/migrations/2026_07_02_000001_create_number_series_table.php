<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50); // sales_invoice, purchase_invoice, payment_voucher, etc.
            $table->string('prefix', 20)->default('');     // e.g. 'SI', 'INV'
            $table->string('format', 100)->default('{prefix}-{sequence:5}');
            $table->integer('next_sequence')->default(1);
            $table->integer('padding')->default(5);        // deprecated — use format {sequence:N}
            $table->string('separator', 5)->default('-');   // deprecated — use format
            $table->boolean('include_branch')->default(false);
            $table->boolean('include_year')->default(false);
            $table->boolean('include_month')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_series');
    }
};
