<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix incorrect conversion factors in item_units table.
 *
 * Correct hierarchy: علبة=1, خرطوشه=10, كرتونة=500
 * Was: كرتونة=50 (wrong), خطوطة=1 in some seeders (wrong)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Get unit IDs by code
        $cartonUnitId = DB::table('units')->where('code', 'CARTON')->value('id');
        $khatootaUnitId = DB::table('units')->where('code', 'KHAToota')->value('id');

        if ($cartonUnitId) {
            // Fix كرتونة: cf should be 500, not 50
            DB::table('item_units')
                ->where('unit_id', $cartonUnitId)
                ->where('conversion_factor', 50)
                ->update(['conversion_factor' => 500]);
        }

        if ($khatootaUnitId) {
            // Fix خطوطة: cf should be 10, not 1
            DB::table('item_units')
                ->where('unit_id', $khatootaUnitId)
                ->where('conversion_factor', 1)
                ->update(['conversion_factor' => 10]);
        }
    }

    public function down(): void
    {
        // Not reversible — old values are lost.
    }
};
