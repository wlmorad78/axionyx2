<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $audits = DB::table('inventory_audits')
            ->where('status', 'posted')
            ->get();

        foreach ($audits as $audit) {
            $transactions = DB::table('inventory_transactions')
                ->where('reference_type', 'App\\Models\\InventoryAudit')
                ->where('reference_id', $audit->id)
                ->get();

            $auditItems = DB::table('inventory_audit_items')
                ->leftJoin('items', 'items.id', '=', 'inventory_audit_items.item_id')
                ->where('inventory_audit_id', $audit->id)
                ->where('variance_qty', '!=', 0)
                ->select('inventory_audit_items.*', 'items.base_unit_id')
                ->selectSub(
                    DB::table('item_units')
                        ->select('unit_id')
                        ->whereColumn('item_units.item_id', 'inventory_audit_items.item_id')
                        ->orderByDesc('is_default')
                        ->orderByDesc('is_purchase_unit')
                        ->limit(1),
                    'fallback_unit_id'
                )
                ->get();

            foreach ($auditItems as $item) {
                $alreadyExists = DB::table('inventory_transaction_items')
                    ->whereIn('inventory_transaction_id', $transactions->pluck('id'))
                    ->where('item_id', $item->item_id)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $transaction = $transactions->first(fn ($value) =>
                    ((float) $item->variance_qty > 0 && $value->transaction_type_id === DB::table('inventory_transaction_types')->where('code', 'STOCK_ADJUSTMENT_ADD')->value('id')) ||
                    ((float) $item->variance_qty < 0 && $value->transaction_type_id === DB::table('inventory_transaction_types')->where('code', 'STOCK_ADJUSTMENT_SUB')->value('id'))
                );

                if (!$transaction) {
                    continue;
                }

                $now = now();
                DB::table('inventory_transaction_items')->insert([
                    'inventory_transaction_id' => $transaction->id,
                    'item_id' => $item->item_id,
                    'unit_id' => $item->unit_id ?: ($item->base_unit_id ?: $item->fallback_unit_id),
                    'qty' => $item->variance_qty,
                    'unit_cost' => $item->purchase_price,
                    'total_cost' => abs((float) $item->variance_cost),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Repair migration is intentionally not reversible to avoid deleting valid stock movements.
    }
};
