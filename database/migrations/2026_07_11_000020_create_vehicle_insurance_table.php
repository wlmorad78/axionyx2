<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_insurance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->string('insurance_company', 255);
            $table->string('policy_number', 100);
            $table->enum('insurance_type', ['comprehensive', 'third_party', 'collision'])->default('comprehensive');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('premium_amount', 14, 2);
            $table->decimal('coverage_amount', 14, 2)->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->string('file_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_insurance');
    }
};
