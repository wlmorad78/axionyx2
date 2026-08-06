<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shelf_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_audit_id')->constrained('merchandising_audits')->cascadeOnDelete();
            $table->foreignId('display_location_id')->constrained('display_locations')->cascadeOnDelete();
            $table->decimal('shelf_length', 8, 2)->nullable();
            $table->decimal('shelf_width', 8, 2)->nullable();
            $table->decimal('shelf_height', 8, 2)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('shelf_audits'); }
};
