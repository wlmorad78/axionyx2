<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->foreignId('pricing_method_id')->nullable()->after('company_id')->constrained('pricing_methods')->nullOnDelete();
            $table->integer('priority')->default(0)->after('pricing_method_id');
            $table->date('effective_from')->nullable()->after('priority');
            $table->date('effective_to')->nullable()->after('effective_from');
            $table->string('status', 20)->default('ACTIVE')->after('effective_to');
        });
    }
    public function down(): void {
        Schema::table('price_lists', function (Blueprint $table) {
            $table->dropForeign(['pricing_method_id']);
            $table->dropColumn(['pricing_method_id', 'priority', 'effective_from', 'effective_to', 'status']);
        });
    }
};
