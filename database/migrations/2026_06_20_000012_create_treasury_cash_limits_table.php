<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_cash_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->decimal('minimum_limit', 14, 4)->default(0);
            $table->decimal('maximum_limit', 14, 4)->default(0);
            $table->decimal('alert_limit', 14, 4)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_cash_limits'); }
};
