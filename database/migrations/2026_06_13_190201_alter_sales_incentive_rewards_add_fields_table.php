<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sales_incentive_rewards', function (Blueprint $table) {
            $table->string('discount_type', 20)->nullable()->after('reward_type');
            $table->foreignId('item_id')->nullable()->after('max_reward')->constrained('items');
            $table->decimal('qty', 12, 2)->nullable()->after('item_id');
        });
    }
    public function down(): void {
        Schema::table('sales_incentive_rewards', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'item_id', 'qty']);
        });
    }
};
