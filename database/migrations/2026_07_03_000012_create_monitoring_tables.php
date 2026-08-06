<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Queue Monitor: track job execution
        Schema::create('queue_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->string('payload');
            $table->string('job_name')->index();
            $table->string('connection')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_tries')->default(3);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['status', 'created_at']);
        });

        // Scheduled Tasks: track scheduled command execution
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->string('schedule')->nullable();
            $table->string('status')->default('pending');
            $table->text('output')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('exit_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique('command');
        });

        // System Health: periodic snapshots
        Schema::create('system_health', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name')->index();
            $table->float('metric_value');
            $table->string('unit')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->index(['metric_name', 'recorded_at']);
        });

        // Activity Log: user and system activity
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->string('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('system_health');
        Schema::dropIfExists('scheduled_tasks');
        Schema::dropIfExists('queue_jobs');
    }
};
