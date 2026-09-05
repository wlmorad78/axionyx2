<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $types = [
            [
                'code' => 'STOCK_ADJUSTMENT_ADD',
                'name' => 'تسوية مخزون (زيادة)',
                'effect' => 'addition',
            ],
            [
                'code' => 'STOCK_ADJUSTMENT_SUB',
                'name' => 'تسوية مخزون (نقص)',
                'effect' => 'subtraction',
            ],
        ];

        foreach ($types as $type) {
            DB::table('inventory_transaction_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, [
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }

        $addTypeId = DB::table('inventory_transaction_types')
            ->where('code', 'STOCK_ADJUSTMENT_ADD')->value('id');
        $subTypeId = DB::table('inventory_transaction_types')
            ->where('code', 'STOCK_ADJUSTMENT_SUB')->value('id');

        $audits = DB::table('inventory_audits')
            ->where('status', 'posted')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('inventory_transactions')
                    ->whereColumn('reference_id', 'inventory_audits.id')
                    ->where('reference_type', 'App\\Models\\InventoryAudit');
            })
            ->get();

        foreach ($audits as $audit) {
            $items = DB::table('inventory_audit_items')
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

            foreach ([[$addTypeId, '>', 'زيادة جرد'], [$subTypeId, '<', 'نقص جرد']] as [$typeId, $operator, $note]) {
                $selected = $items->filter(fn ($item) => $operator === '>'
                    ? (float) $item->variance_qty > 0
                    : (float) $item->variance_qty < 0);

                if ($selected->isEmpty()) {
                    continue;
                }

                $last = DB::table('inventory_transactions')
                    ->where('transaction_no', 'like', 'INV-%')
                    ->orderByDesc('id')
                    ->value('transaction_no');
                $next = $last && preg_match('/^INV-(\d+)$/', $last, $matches)
                    ? ((int) $matches[1]) + 1
                    : 1;
                $transactionNo = 'INV-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
                $now = now();

                $transactionId = DB::table('inventory_transactions')->insertGetId([
                    'company_id' => $audit->company_id,
                    'branch_id' => $audit->branch_id,
                    'transaction_type_id' => $typeId,
                    'warehouse_id' => $audit->warehouse_id,
                    'transaction_no' => $transactionNo,
                    'transaction_date' => $audit->audit_date,
                    'transaction_time' => $now->format('H:i:s'),
                    'reference_type' => 'App\\Models\\InventoryAudit',
                    'reference_id' => $audit->id,
                    'notes' => "جرد مخزون #{$audit->audit_no} - {$note}",
                    'status' => 'posted',
                    'created_by' => $audit->created_by,
                    'approved_by' => $audit->approved_by,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($selected as $item) {
                    $qty = (float) $item->variance_qty;
                    DB::table('inventory_transaction_items')->insert([
                        'inventory_transaction_id' => $transactionId,
                        'item_id' => $item->item_id,
                        'unit_id' => $item->unit_id ?: ($item->base_unit_id ?: $item->fallback_unit_id),
                        'qty' => $qty,
                        'unit_cost' => $item->purchase_price,
                        'total_cost' => abs((float) $item->variance_cost),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('inventory_transaction_types')
            ->whereIn('code', ['STOCK_ADJUSTMENT_ADD', 'STOCK_ADJUSTMENT_SUB'])
            ->delete();
    }
};
