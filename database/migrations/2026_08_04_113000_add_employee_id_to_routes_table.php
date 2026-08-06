<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('routes', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('sales_territory_id')->constrained('employees');
        });
    }
    public function down(): void {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
