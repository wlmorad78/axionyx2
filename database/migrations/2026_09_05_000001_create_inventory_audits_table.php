<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('audit_no', 50)->unique();
            $table->date('audit_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_audits');
    }
};
