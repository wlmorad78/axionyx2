<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\UnitConversionService;

/**
 * Backfill conversion_factor and base_quantity on all existing item records.
 *
 * This reads the current item_units table to resolve conversion factors
 * and populates the new columns so historical data is accurate.
 */
return new class extends Migration
{
    public function up(): void
    {
        $service = app(UnitConversionService::class);

        // ── sales_invoice_items ──
        $this->backfillTable('sales_invoice_items', 'qty', $service);

        // ── purchase_invoice_items ──
        $this->backfillTable('purchase_invoice_items', 'qty', $service);

        // ── load_request_items ──
        $this->backfillTable('load_request_items', 'quantity', $service);

        // ── issue_order_items ── backfill unit_id from item_unit_id, then conversion_factor
        $this->backfillIssueOrderItems($service);

        // ── inventory_transaction_items ── ensure conversion_factor is set
        $this->backfillInventoryTransactionItems($service);
    }

    public function down(): void
    {
        // Data migration is not reversible — old values are lost.
    }

    private function backfillTable(string $table, string $qtyColumn, UnitConversionService $service): void
    {
        $rows = DB::table($table)
            ->whereNull('deleted_at')
            ->where('conversion_factor', 1)
            ->where('base_quantity', 0)
            ->where($qtyColumn, '>', 0)
            ->get();

        foreach ($rows as $row) {
            $unitId = $row->unit_id ?? null;
            $item_id = $row->item_id;
            $qty = (float) $row->{$qtyColumn};

            $cf = 1.0;
            if ($unitId) {
                $cf = $service->getConversionFactor($item_id, $unitId);
            }

            DB::table($table)
                ->where('id', $row->id)
                ->update([
                    'conversion_factor' => $cf,
                    'base_quantity'     => $qty * $cf,
                ]);
        }
    }

    private function backfillIssueOrderItems(UnitConversionService $service): void
    {
        $rows = DB::table('issue_order_items')
            ->whereNull('deleted_at')
            ->get();

        foreach ($rows as $row) {
            $unitId = $row->unit_id ?? null;

            // If unit_id is null but item_unit_id exists, resolve unit_id from item_unit
            if (!$unitId && $row->item_unit_id) {
                $iu = DB::table('item_units')
                    ->where('id', $row->item_unit_id)
                    ->first();
                if ($iu) {
                    $unitId = $iu->unit_id;
                    DB::table('issue_order_items')
                        ->where('id', $row->id)
                        ->update(['unit_id' => $unitId]);
                }
            }

            $cf = 1.0;
            if ($unitId) {
                $cf = $service->getConversionFactor($row->item_id, $unitId);
            }

            $requested = (float) $row->requested_quantity;
            $issued = (float) $row->issued_quantity;

            DB::table('issue_order_items')
                ->where('id', $row->id)
                ->update([
                    'conversion_factor' => $cf,
                    'base_quantity'     => ($requested > 0 ? $requested : $issued) * $cf,
                ]);
        }
    }

    private function backfillInventoryTransactionItems(UnitConversionService $service): void
    {
        $rows = DB::table('inventory_transaction_items')
            ->where('conversion_factor', 1)
            ->get();

        foreach ($rows as $row) {
            $unitId = $row->unit_id ?? null;
            $item_id = $row->item_id;

            $cf = 1.0;
            if ($unitId) {
                $cf = $service->getConversionFactor($item_id, $unitId);
            }

            DB::table('inventory_transaction_items')
                ->where('id', $row->id)
                ->update(['conversion_factor' => $cf]);
        }
    }
};
