<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_batch_id')->constrained('sync_batches');
            $table->string('table_name');
            $table->integer('record_id');
            $table->enum('operation', ['create', 'update', 'delete']);
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
