<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $issueType = DB::table('inventory_transaction_types')->where('code', 'ISSUE')->first();
        $issueOrderType = DB::table('inventory_transaction_types')->where('code', 'ISSUE_ORDER')->first();

        if ($issueType && !$issueOrderType) {
            DB::table('inventory_transaction_types')
                ->where('id', $issueType->id)
                ->update(['code' => 'ISSUE_ORDER', 'name' => 'أمر صرف']);
        } elseif ($issueType && $issueOrderType) {
            DB::table('inventory_transactions')
                ->where('transaction_type_id', $issueType->id)
                ->update(['transaction_type_id' => $issueOrderType->id]);
            DB::table('inventory_transaction_types')
                ->where('id', $issueType->id)
                ->delete();
        }
    }

    public function down(): void
    {
        // Not reversible safely
    }
};
