<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('work_days')) {
            return;
        }

        Schema::create('work_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->constrained('branches');
            $table->integer('year');
            $table->integer('month');
            $table->json('work_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_days');
    }
};
