<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('return_order_items', function (Blueprint $table) {
            $table->decimal('t_in_qty', 12, 2)->default(0)->after('loaded_qty');
            $table->decimal('t_out_qty', 12, 2)->default(0)->after('t_in_qty');
        });
    }
    public function down(): void {
        Schema::table('return_order_items', function (Blueprint $table) {
            $table->dropColumn(['t_in_qty', 't_out_qty']);
        });
    }
};
