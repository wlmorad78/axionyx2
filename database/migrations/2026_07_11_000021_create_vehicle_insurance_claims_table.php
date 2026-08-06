<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('vehicle_insurance_id')->nullable()->constrained('vehicle_insurance');
            $table->foreignId('vehicle_accident_id')->nullable()->constrained('vehicle_accidents');
            $table->string('claim_no', 50);
            $table->date('claim_date');
            $table->decimal('claim_amount', 14, 2);
            $table->decimal('approved_amount', 14, 2)->nullable();
            $table->enum('status', ['filed', 'under_review', 'approved', 'rejected', 'settled'])->default('filed');
            $table->date('settlement_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'claim_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_insurance_claims');
    }
};
