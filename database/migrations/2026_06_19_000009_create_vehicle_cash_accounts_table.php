<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('treasury_id')->nullable()->constrained('treasuries')->nullOnDelete();
            $table->decimal('opening_balance', 14, 4)->default(0);
            $table->decimal('current_balance', 14, 4)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vehicle_cash_accounts'); }
};
