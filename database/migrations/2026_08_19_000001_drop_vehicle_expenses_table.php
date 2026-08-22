<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('vehicle_expenses');
    }

    public function down(): void {
        Schema::create('vehicle_expenses', function ($table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->date('expense_date');
            $table->enum('expense_type', ['toll', 'parking', 'fine', 'other'])->default('other');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
