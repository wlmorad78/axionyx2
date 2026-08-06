<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchandising_task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_task_id')->constrained('merchandising_tasks')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('assigned_date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['PENDING', 'COMPLETED', 'OVERDUE'])->default('PENDING');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('merchandising_task_assignments'); }
};
