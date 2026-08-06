<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('treasury_closing_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_daily_closing_id')->constrained('treasury_daily_closings')->cascadeOnDelete();
            $table->string('transaction_type');
            $table->decimal('amount', 14, 4)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('treasury_closing_details'); }
};
