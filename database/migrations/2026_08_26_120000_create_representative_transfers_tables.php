<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representative_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transfer_no', 50);
            $table->uuid('client_uuid')->nullable();
            $table->foreignId('from_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('to_employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('status', 20)->default('posted');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'transfer_no']);
            $table->unique(['company_id', 'client_uuid']);
            $table->index(['company_id', 'from_employee_id', 'status']);
        });

        Schema::create('representative_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('representative_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('base_quantity', 12, 2);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->string('batch_no')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->index(['representative_transfer_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representative_transfer_items');
        Schema::dropIfExists('representative_transfers');
    }
};
