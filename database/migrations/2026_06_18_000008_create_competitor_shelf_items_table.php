<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('competitor_shelf_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_audit_id')->constrained('shelf_audits')->cascadeOnDelete();
            $table->foreignId('competitor_product_id')->constrained('competitor_products')->cascadeOnDelete();
            $table->integer('facings_count')->default(0);
            $table->decimal('shelf_share_percent', 5, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('competitor_shelf_items'); }
};
