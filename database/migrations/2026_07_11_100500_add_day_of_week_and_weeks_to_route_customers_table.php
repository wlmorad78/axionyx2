<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('route_customers', function (Blueprint $table) {
            $table->string('day_of_week', 20)->nullable()->after('visit_frequency');
            $table->string('weeks', 50)->nullable()->after('day_of_week');
        });
    }

    public function down(): void {
        Schema::table('route_customers', function (Blueprint $table) {
            $table->dropColumn(['day_of_week', 'weeks']);
        });
    }
};
