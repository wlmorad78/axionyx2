<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('tax_registration_no')->nullable();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->foreignId('tax_exemption_id')->nullable()->constrained('tax_exemptions')->nullOnDelete();
            $table->boolean('is_taxable')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('customer_tax_profiles'); }
};
