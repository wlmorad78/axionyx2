<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sales_incentives', function (Blueprint $table) {
            $table->string('promotion_type', 50)->after('name_en')->default('fixed_discount');
            $table->integer('priority')->after('is_active')->default(0);
        });
    }
    public function down(): void {
        Schema::table('sales_incentives', function (Blueprint $table) {
            $table->dropColumn(['promotion_type', 'priority']);
        });
    }
};
