<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_provider_id')->constrained('integration_providers');
            $table->string('endpoint_name');
            $table->string('http_method', 10);
            $table->string('endpoint_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_endpoints');
    }
};
