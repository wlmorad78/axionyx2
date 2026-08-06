<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('availability_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_audit_id')->constrained('merchandising_audits')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->boolean('is_available')->default(true);
            $table->integer('stock_qty')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('availability_audits'); }
};
