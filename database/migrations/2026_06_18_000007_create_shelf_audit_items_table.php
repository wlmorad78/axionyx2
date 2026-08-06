<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shelf_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_audit_id')->constrained('shelf_audits')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('facings_count')->default(0);
            $table->integer('display_qty')->default(0);
            $table->decimal('shelf_share_percent', 5, 2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('shelf_audit_items'); }
};
