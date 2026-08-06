<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('distribution_plan_reps', function (Blueprint $table) {
            $table->foreignId('route_id')->nullable()->after('sales_rep_id')->constrained('routes');
        });
    }
    public function down(): void {
        Schema::table('distribution_plan_reps', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropColumn('route_id');
        });
    }
};
