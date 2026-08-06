<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('posm_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_audit_id')->constrained('merchandising_audits')->cascadeOnDelete();
            $table->foreignId('marketing_material_id')->nullable()->constrained('marketing_materials')->nullOnDelete();
            $table->boolean('is_available')->default(true);
            $table->enum('condition_status', ['GOOD', 'DAMAGED', 'MISSING'])->default('MISSING');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('posm_audits'); }
};
