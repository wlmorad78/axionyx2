<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_id')->constrained('treasuries')->cascadeOnDelete();
            $table->enum('alert_type', ['LOW_CASH', 'HIGH_CASH', 'SHORTAGE', 'OVERAGE']);
            $table->date('alert_date');
            $table->text('message');
            $table->enum('status', ['NEW', 'READ', 'DISMISSED'])->default('NEW');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_alerts'); }
};
