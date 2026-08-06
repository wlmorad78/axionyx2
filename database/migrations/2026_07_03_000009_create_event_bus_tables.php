<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('category')->default('system');
            $table->string('source_module')->nullable();
            $table->text('description')->nullable();
            $table->json('payload_schema')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('event_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->string('status')->default('fired');
            $table->json('processed_by')->nullable();
            $table->timestamp('fired_at');
            $table->timestamps();
        });

        Schema::create('event_listeners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_definition_id')->constrained()->cascadeOnDelete();
            $table->string('listener_class');
            $table->string('module_code')->nullable();
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_definition_id')->constrained()->cascadeOnDelete();
            $table->string('module_code');
            $table->string('handler_class');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique(['event_definition_id', 'module_code', 'handler_class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_subscriptions');
        Schema::dropIfExists('event_listeners');
        Schema::dropIfExists('event_logs');
        Schema::dropIfExists('event_definitions');
    }
};
