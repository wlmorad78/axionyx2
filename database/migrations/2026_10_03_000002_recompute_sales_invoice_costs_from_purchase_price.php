<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $latestPurchasePrice = '
            (
                SELECT pii.price
                FROM purchase_invoice_items pii
                JOIN purchase_invoices pi ON pi.id = pii.purchase_invoice_id
                WHERE pii.item_id = sales_invoice_items.item_id
                  AND pii.deleted_at IS NULL
                  AND pi.deleted_at IS NULL
                ORDER BY pi.invoice_date DESC, pii.id DESC
                LIMIT 1
            )';

        $catalogPurchasePrice = '
            (
                SELECT iu.purchase_price
                FROM item_units iu
                WHERE iu.item_id = sales_invoice_items.item_id
                  AND iu.is_default = 1
                  AND iu.deleted_at IS NULL
                  AND iu.purchase_price IS NOT NULL
                ORDER BY iu.id DESC
                LIMIT 1
            )';

        $unitCostSql = "COALESCE({$catalogPurchasePrice}, {$latestPurchasePrice}, unit_cost)";

        DB::statement("
            UPDATE sales_invoice_items
            SET unit_cost = {$unitCostSql},
                total_cost = ABS(qty) * {$unitCostSql}
            WHERE deleted_at IS NULL
        ");

        DB::statement('
            UPDATE sales_invoice_items
            SET profit = ROUND(COALESCE(net_amount, 0) - COALESCE(total_cost, 0), 2)
            WHERE deleted_at IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('UPDATE sales_invoice_items SET unit_cost = 47.85, total_cost = qty * 47.85');
        DB::statement('
            UPDATE sales_invoice_items
            SET profit = ROUND(COALESCE(net_amount, 0) - COALESCE(total_cost, 0), 2)
            WHERE deleted_at IS NULL
        ');
    }
};
