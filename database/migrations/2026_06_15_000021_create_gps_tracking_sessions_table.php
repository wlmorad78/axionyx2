<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('employees');
            $table->foreignId('route_id')->nullable()->constrained('routes');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_tracking_sessions');
    }
};
