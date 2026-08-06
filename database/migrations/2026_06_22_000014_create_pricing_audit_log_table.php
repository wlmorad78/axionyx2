<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pricing_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('rule_applied')->nullable();
            $table->decimal('old_price', 14, 4)->default(0);
            $table->decimal('new_price', 14, 4)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pricing_audit_log'); }
};
