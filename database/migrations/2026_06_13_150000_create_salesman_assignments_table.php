<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('salesman_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('sales_territory_id')->constrained('sales_territories');
            $table->string('job_role', 50); // salesman, supervisor, area_manager, sales_manager, merchandiser
            $table->unsignedBigInteger('parent_assignment_id')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('parent_assignment_id')->references('id')->on('salesman_assignments');
        });
    }
    public function down(): void {
        Schema::dropIfExists('salesman_assignments');
    }
};
