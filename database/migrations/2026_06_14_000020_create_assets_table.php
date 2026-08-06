<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_id')->constrained('asset_categories');
            $table->string('asset_code', 50)->unique();
            $table->string('asset_name', 255);
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 12, 2);
            $table->decimal('current_value', 12, 2);
            $table->enum('status', ['active','disposed','transferred'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('assets'); }
};
