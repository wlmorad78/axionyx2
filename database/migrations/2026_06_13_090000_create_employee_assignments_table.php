<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('sales_territory_id')->nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->unsignedBigInteger('job_grade_id')->nullable();
            $table->unsignedBigInteger('salary_scale_id')->nullable();
            $table->unsignedBigInteger('direct_manager_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('branch_id')->references('id')->on('branches');
            $table->foreign('organization_unit_id')->references('id')->on('organization_units');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            $table->foreign('sales_territory_id')->references('id')->on('sales_territories');
            $table->foreign('job_title_id')->references('id')->on('job_titles');
            $table->foreign('job_grade_id')->references('id')->on('job_grades');
            $table->foreign('salary_scale_id')->references('id')->on('salary_scales');
            $table->foreign('direct_manager_id')->references('id')->on('employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_assignments');
    }
};
