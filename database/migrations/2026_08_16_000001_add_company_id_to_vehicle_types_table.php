<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::statement('ALTER TABLE vehicle_types ADD COLUMN company_id INTEGER NULL REFERENCES companies(id) ON DELETE SET NULL');
            } else {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
