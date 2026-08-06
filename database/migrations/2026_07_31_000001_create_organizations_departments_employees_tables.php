<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Position Levels - used by Job Positions
        if (!Schema::hasTable('position_levels')) {
            Schema::create('position_levels', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Organizational Levels - used by Organization Units
        if (!Schema::hasTable('organizational_levels')) {
            Schema::create('organizational_levels', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->integer('level_order')->default(0);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Organization Unit Types
        if (!Schema::hasTable('organization_unit_types')) {
            Schema::create('organization_unit_types', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Organization Units (companies within the org structure)
        if (!Schema::hasTable('organization_units')) {
            Schema::create('organization_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies');
                $table->foreignId('organization_unit_type_id')->constrained('organization_unit_types');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('code', 30)->unique();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->foreignId('organizational_level_id')->constrained('organizational_levels');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('parent_id')->references('id')->on('organization_units')->nullOnDelete();
            });
        }

        // 5. Job Families
        if (!Schema::hasTable('job_families')) {
            Schema::create('job_families', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies');
                $table->string('code', 30)->unique();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 6. Job Grades
        if (!Schema::hasTable('job_grades')) {
            Schema::create('job_grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies');
                $table->string('code', 30)->unique();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->integer('grade_level')->default(1);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 7. Job Titles (belong to a job family)
        if (!Schema::hasTable('job_titles')) {
            Schema::create('job_titles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies');
                $table->unsignedBigInteger('job_family_id');
                $table->string('code', 30)->unique();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('job_family_id')->references('id')->on('job_families');
            });
        }

        // 8. Departments (hierarchical, managed by a user)
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies');
                $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 9. Job Positions (hierarchical, linked to department/organization unit)
        if (!Schema::hasTable('job_positions')) {
            Schema::create('job_positions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies');
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
                $table->foreignId('position_level_id')->nullable()->constrained('position_levels')->nullOnDelete();
                $table->foreignId('job_title_id')->nullable()->constrained('job_titles')->nullOnDelete();
                $table->foreignId('job_grade_id')->nullable()->constrained('job_grades')->nullOnDelete();
                $table->foreignId('salary_scale_id')->nullable()->constrained('salary_scales')->nullOnDelete();
                $table->unsignedBigInteger('reports_to_position_id')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('job_positions')->nullOnDelete();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_manager')->default(false);
                $table->integer('vacancy_count')->default(0);
                $table->integer('filled_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('reports_to_position_id')->references('id')->on('job_positions')->nullOnDelete();
            });
        }

        // 10. Employee Statuses (static lookup)
        if (!Schema::hasTable('employee_statuses')) {
            Schema::create('employee_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name_ar');
                $table->string('name_en')->nullable();
                $table->string('color', 20)->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 11. Employees
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('employee_code', 30)->unique();

                $table->string('first_name_ar');
                $table->string('second_name_ar')->nullable();
                $table->string('third_name_ar')->nullable();
                $table->string('last_name_ar');

                $table->string('first_name_en')->nullable();
                $table->string('second_name_en')->nullable();
                $table->string('third_name_en')->nullable();
                $table->string('last_name_en')->nullable();

                $table->string('national_id', 30)->nullable()->unique();
                $table->string('passport_number', 30)->nullable();

                $table->date('birth_date')->nullable();
                $table->enum('gender', ['male', 'female'])->nullable();
                $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();

                $table->string('mobile', 20)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email')->nullable();

                $table->unsignedBigInteger('country_id')->nullable();
                $table->unsignedBigInteger('governorate_id')->nullable();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->unsignedBigInteger('area_id')->nullable();
                $table->string('address_line_1')->nullable();

                $table->unsignedBigInteger('employee_status_id')->nullable();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('job_position_id')->nullable()->constrained('job_positions')->nullOnDelete();
                $table->date('hire_date')->nullable();
                $table->date('termination_date')->nullable();

                $table->unsignedBigInteger('photo_attachment_id')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                if (Schema::hasTable('countries')) {
                    $table->foreign('country_id')->references('id')->on('countries');
                }
                if (Schema::hasTable('governorates')) {
                    $table->foreign('governorate_id')->references('id')->on('governorates');
                }
                if (Schema::hasTable('cities')) {
                    $table->foreign('city_id')->references('id')->on('cities');
                }
                if (Schema::hasTable('districts')) {
                    $table->foreign('area_id')->references('id')->on('districts');
                }
                if (Schema::hasTable('employee_statuses')) {
                    $table->foreign('employee_status_id')->references('id')->on('employee_statuses');
                }
                if (Schema::hasTable('users')) {
                    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                }
            });
        }

        // 12. Employee Assignments (historical position assignments per employee)
        if (!Schema::hasTable('employee_assignments')) {
            Schema::create('employee_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('organization_unit_id')->nullable();
                $table->unsignedBigInteger('position_id')->nullable();
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

                if (Schema::hasTable('branches')) {
                    $table->foreign('branch_id')->references('id')->on('branches');
                }
                if (Schema::hasTable('organization_units')) {
                    $table->foreign('organization_unit_id')->references('id')->on('organization_units');
                }
                if (Schema::hasTable('job_positions')) {
                    $table->foreign('position_id')->references('id')->on('job_positions')->nullOnDelete();
                }
                if (Schema::hasTable('cost_centers')) {
                    $table->foreign('cost_center_id')->references('id')->on('cost_centers');
                }
                if (Schema::hasTable('sales_territories')) {
                    $table->foreign('sales_territory_id')->references('id')->on('sales_territories');
                }
                if (Schema::hasTable('job_titles')) {
                    $table->foreign('job_title_id')->references('id')->on('job_titles');
                }
                if (Schema::hasTable('job_grades')) {
                    $table->foreign('job_grade_id')->references('id')->on('job_grades');
                }
                if (Schema::hasTable('salary_scales')) {
                    $table->foreign('salary_scale_id')->references('id')->on('salary_scales');
                }
                $table->foreign('direct_manager_id')->references('id')->on('employees')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_assignments');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('job_positions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('job_titles');
        Schema::dropIfExists('job_grades');
        Schema::dropIfExists('job_families');
        Schema::dropIfExists('organization_units');
        Schema::dropIfExists('organization_unit_types');
        Schema::dropIfExists('organizational_levels');
        Schema::dropIfExists('position_levels');
    }
};
