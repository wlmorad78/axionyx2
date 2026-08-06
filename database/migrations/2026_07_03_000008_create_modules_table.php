<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('version')->default('1.0.0');
            $table->string('status')->default('installed'); // installed, disabled, pending
            $table->boolean('is_core')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->json('dependencies')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('config')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('author')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
