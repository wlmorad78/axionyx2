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
                DB::statement('ALTER TABLE vehicle_types ADD COLUMN code VARCHAR(50) NOT NULL DEFAULT ""');
            } else {
                $table->string('code', 50)->after('id');
            }
            $table->string('icon', 50)->nullable()->after('description');
            $table->integer('sort_order')->default(0)->after('icon');
            $table->boolean('is_active')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropColumn(['code', 'icon', 'sort_order', 'is_active']);
        });
    }
};
