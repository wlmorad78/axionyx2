<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchandising_audit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_audit_id')->constrained('merchandising_audits')->cascadeOnDelete();
            $table->foreignId('merchandising_standard_item_id')->constrained('merchandising_standard_items')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('merchandising_audit_details'); }
};
