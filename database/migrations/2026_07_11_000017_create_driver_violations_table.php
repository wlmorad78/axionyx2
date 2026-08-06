<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('driver_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers');
            $table->date('violation_date');
            $table->string('violation_type', 100);
            $table->text('description')->nullable();
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->integer('points')->default(0);
            $table->enum('status', ['pending', 'paid', 'contested', 'dismissed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_violations');
    }
};
