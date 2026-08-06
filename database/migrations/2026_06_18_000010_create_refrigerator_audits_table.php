<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('refrigerator_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandising_audit_id')->constrained('merchandising_audits')->cascadeOnDelete();
            $table->foreignId('marketing_asset_id')->nullable()->constrained('marketing_assets')->nullOnDelete();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('cleanliness_score', 5, 2)->default(0);
            $table->enum('working_status', ['WORKING', 'NEEDS_MAINTENANCE', 'OUT_OF_SERVICE'])->default('WORKING');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('refrigerator_audits'); }
};
