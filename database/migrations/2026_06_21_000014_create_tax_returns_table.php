<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tax_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('tax_period_id')->constrained('tax_periods')->cascadeOnDelete();
            $table->string('return_no', 50)->unique();
            $table->date('submission_date')->nullable();
            $table->decimal('total_sales', 14, 4)->default(0);
            $table->decimal('total_purchases', 14, 4)->default(0);
            $table->decimal('output_tax', 14, 4)->default(0);
            $table->decimal('input_tax', 14, 4)->default(0);
            $table->decimal('net_tax', 14, 4)->default(0);
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'APPROVED'])->default('DRAFT');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('tax_returns'); }
};
