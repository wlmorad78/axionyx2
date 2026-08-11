<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\UnitConversionService;

return new class extends Migration {
    public function up(): void
    {
        $unitService = app(UnitConversionService::class);

        $zeroItems = DB::table('inventory_transaction_items')
            ->where('qty', 0)
            ->get();

        foreach ($zeroItems as $item) {
            $txn = DB::table('inventory_transactions')
                ->where('id', $item->inventory_transaction_id)
                ->first();

            if (!$txn) continue;

            $newQty = 0;

            if ($txn->reference_type === 'App\\Models\\Purchase\\PurchaseInvoice' && $txn->reference_id) {
                $pItem = DB::table('purchase_invoice_items')
                    ->where('purchase_invoice_id', $txn->reference_id)
                    ->where('item_id', $item->item_id)
                    ->first();

                if ($pItem) {
                    $qty = (float) ($pItem->qty ?? 0);
                    if ((float) ($pItem->conversion_factor ?? 0) > 0 && (float) ($pItem->base_quantity ?? 0) > 0) {
                        $newQty = (float) $pItem->base_quantity;
                    } else {
                        $cf = (float) ($pItem->conversion_factor ?? 1);
                        $newQty = $qty * $cf;
                    }
                }
            } elseif ($txn->reference_type === 'App\\Models\\Purchase\\PurchaseReceipt' && $txn->reference_id) {
                $prItem = DB::table('purchase_receipt_items')
                    ->where('purchase_receipt_id', $txn->reference_id)
                    ->where('item_id', $item->item_id)
                    ->first();

                if ($prItem) {
                    $qty = (float) ($prItem->qty ?? 0);
                    $cf = (float) ($prItem->conversion_factor ?? 1);
                    $newQty = $qty * $cf;
                }
            }

            if ($newQty > 0) {
                DB::table('inventory_transaction_items')
                    ->where('id', $item->id)
                    ->update([
                        'qty' => $newQty,
                        'conversion_factor' => $item->conversion_factor ?: 1,
                    ]);
            }
        }
    }

    public function down(): void
    {
    }
};
