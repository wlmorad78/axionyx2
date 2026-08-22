<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('return_order_items', function (Blueprint $table) {
            $table->decimal('loaded_qty', 12, 2)->default(0)->after('sold_quantity');
        });
    }
    public function down(): void {
        Schema::table('return_order_items', function (Blueprint $table) {
            $table->dropColumn('loaded_qty');
        });
    }
};
