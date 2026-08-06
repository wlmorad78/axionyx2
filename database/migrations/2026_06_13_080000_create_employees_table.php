<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
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

            $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('governorate_id')->references('id')->on('governorates');
            $table->foreign('city_id')->references('id')->on('cities');
            $table->foreign('area_id')->references('id')->on('districts');
            $table->foreign('employee_status_id')->references('id')->on('employee_statuses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
