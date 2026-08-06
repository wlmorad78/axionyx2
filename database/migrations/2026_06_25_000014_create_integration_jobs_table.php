<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_account_id')->constrained('integration_accounts');
            $table->string('job_name');
            $table->string('schedule_type', 20)->default('REALTIME');
            $table->timestamp('next_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_jobs');
    }
};
