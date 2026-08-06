<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_accidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('driver_id')->nullable()->constrained('drivers');
            $table->dateTime('accident_date');
            $table->string('location', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('police_report_no', 100)->nullable();
            $table->boolean('at_fault')->default(false);
            $table->string('other_party_name', 255)->nullable();
            $table->string('other_party_phone', 50)->nullable();
            $table->string('other_party_insurance', 255)->nullable();
            $table->decimal('repair_cost', 14, 2)->default(0);
            $table->decimal('insurance_claim_amount', 14, 2)->default(0);
            $table->enum('insurance_claim_status', ['none', 'filed', 'approved', 'rejected', 'settled'])->default('none');
            $table->enum('status', ['reported', 'investigating', 'resolved', 'closed'])->default('reported');
            $table->json('images')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_accidents');
    }
};
