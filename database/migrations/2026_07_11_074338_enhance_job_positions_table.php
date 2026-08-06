<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->foreignId('organization_unit_id')->nullable()->after('code')->constrained()->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->after('organization_unit_id')->constrained()->nullOnDelete();
            $table->foreignId('job_grade_id')->nullable()->after('job_title_id')->constrained()->nullOnDelete();
            $table->foreignId('salary_scale_id')->nullable()->after('job_grade_id')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('reports_to_position_id')->nullable()->after('salary_scale_id');
            $table->foreign('reports_to_position_id')->references('id')->on('job_positions')->nullOnDelete();
            $table->boolean('is_manager')->default(false)->after('reports_to_position_id');
            $table->integer('vacancy_count')->default(0)->after('is_manager');
            $table->integer('filled_count')->default(0)->after('vacancy_count');
        });
    }

    public function down(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropForeign(['reports_to_position_id']);
            $table->dropColumn([
                'organization_unit_id', 'job_title_id', 'job_grade_id',
                'salary_scale_id', 'reports_to_position_id', 'is_manager',
                'vacancy_count', 'filled_count',
            ]);
        });
    }
};
