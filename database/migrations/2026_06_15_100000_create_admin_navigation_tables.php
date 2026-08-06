<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('admin_screen_roles');
        Schema::dropIfExists('admin_screens');
        Schema::dropIfExists('admin_modules');

        Schema::create('admin_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('admin_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('admin_modules')->cascadeOnDelete();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('route')->nullable();
            $table->string('api_resource')->nullable();
            $table->string('screen_type')->default('resource');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('admin_screen_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained('admin_screens')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['screen_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_screen_roles');
        Schema::dropIfExists('admin_screens');
        Schema::dropIfExists('admin_modules');
    }
};
