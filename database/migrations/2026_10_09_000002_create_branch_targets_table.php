<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branch_targets')) {
            return;
        }

        Schema::create('branch_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('branch_id')->constrained('branches');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('target_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_targets');
    }
};
