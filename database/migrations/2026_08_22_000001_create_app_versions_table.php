<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->integer('build');
            $table->string('platform')->default('android');
            $table->string('download_url')->nullable();
            $table->boolean('force_update')->default(false);
            $table->json('release_notes')->nullable();
            $table->date('release_date')->nullable();
            $table->string('minimum_supported_version')->nullable();
            $table->integer('minimum_supported_build')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('checksum')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['build', 'platform']);
            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
