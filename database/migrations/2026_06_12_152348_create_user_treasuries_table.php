<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_treasuries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treasury_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'treasury_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_treasuries');
    }
};
