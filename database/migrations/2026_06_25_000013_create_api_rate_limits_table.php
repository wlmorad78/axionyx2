<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients');
            $table->integer('requests_per_minute')->default(60);
            $table->integer('requests_per_hour')->default(1000);
            $table->integer('requests_per_day')->default(10000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_rate_limits');
    }
};
