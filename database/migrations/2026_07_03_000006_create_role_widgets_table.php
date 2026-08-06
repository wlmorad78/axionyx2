<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dashboard_widget_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('width')->default(1);
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique(['role_id', 'dashboard_widget_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_widgets');
    }
};
