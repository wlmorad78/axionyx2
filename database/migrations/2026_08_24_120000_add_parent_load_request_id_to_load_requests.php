<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('load_requests', function (Blueprint $table) {
            $table->foreignId('parent_load_request_id')
                ->nullable()
                ->after('request_no')
                ->constrained('load_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('load_requests', function (Blueprint $table) {
            $table->dropForeign(['parent_load_request_id']);
            $table->dropColumn('parent_load_request_id');
        });
    }
};
