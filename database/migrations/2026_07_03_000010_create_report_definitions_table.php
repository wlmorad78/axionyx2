<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('category')->default('custom');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('base_table');
            $table->json('selected_columns');
            $table->json('filters')->nullable();
            $table->json('sort_by')->nullable();
            $table->json('group_by')->nullable();
            $table->json('aggregations')->nullable();
            $table->json('chart_config')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_template')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('run_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('report_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('permission')->default('view');
            $table->timestamps();
            $table->unique(['report_definition_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_shares');
        Schema::dropIfExists('report_definitions');
    }
};
