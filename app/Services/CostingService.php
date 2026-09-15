<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryTransactionType;

/**
 * CostingService - Moving Average Cost (المتوسط المتحرك)
 *
 * Calculates the weighted average cost of inventory items dynamically.
 * When a purchase occurs: new average = (old_stock * old_avg + new_qty * new_price) / (old_stock + new_qty)
 * When a sale occurs: the current average cost is used as the COGS.
 */
class CostingService
{
    /**
     * Get the current moving average cost for an item in a specific warehouse.
     *
     * @param int $itemId
     * @param int|null $warehouseId
     * @return float
     */
    public function getCurrentAverageCost(int $itemId, ?int $warehouseId = null): float
    {
        $result = DB::table('inventory_transaction_items as iti')
            ->join('inventory_transactions as it', 'it.id', '=', 'iti.inventory_transaction_id')
            ->join('inventory_transaction_types as itt', 'itt.id', '=', 'it.transaction_type_id')
            ->where('iti.item_id', $itemId)
            ->where('it.status', 'posted')
            ->when($warehouseId, fn($q) => $q->where('it.warehouse_id', $warehouseId))
            ->selectRaw('
                SUM(CASE WHEN itt.effect = "addition" THEN iti.qty ELSE 0 END) as total_incoming_qty,
                SUM(CASE WHEN itt.effect = "addition" THEN iti.total_cost ELSE 0 END) as total_incoming_cost,
                SUM(CASE WHEN itt.effect = "subtraction" THEN ABS(iti.qty) ELSE 0 END) as total_outgoing_qty
            ')
            ->first();

        $totalIncomingQty = (float) ($result->total_incoming_qty ?? 0);
        $totalIncomingCost = (float) ($result->total_incoming_cost ?? 0);
        $totalOutgoingQty = (float) ($result->total_outgoing_qty ?? 0);

        $currentStock = $totalIncomingQty - $totalOutgoingQty;

        if ($currentStock <= 0 || $totalIncomingQty <= 0) {
            return $this->getFallbackCost($itemId);
        }

        return $totalIncomingCost / $totalIncomingQty;
    }

    /**
     * Calculate the moving average cost for a sale transaction.
     * Returns the average cost at the time of the sale.
     *
     * @param int $itemId
     * @param int|null $warehouseId
     * @param string $date The date of the sale (for historical accuracy)
     * @return float
     */
    public function getCostAtDate(int $itemId, ?int $warehouseId, string $date): float
    {
        $result = DB::table('inventory_transaction_items as iti')
            ->join('inventory_transactions as it', 'it.id', '=', 'iti.inventory_transaction_id')
            ->join('inventory_transaction_types as itt', 'itt.id', '=', 'it.transaction_type_id')
            ->where('iti.item_id', $itemId)
            ->where('it.status', 'posted')
            ->whereDate('it.transaction_date', '<=', $date)
            ->when($warehouseId, fn($q) => $q->where('it.warehouse_id', $warehouseId))
            ->selectRaw('
                SUM(CASE WHEN itt.effect = "addition" THEN iti.qty ELSE 0 END) as total_incoming_qty,
                SUM(CASE WHEN itt.effect = "addition" THEN iti.total_cost ELSE 0 END) as total_incoming_cost,
                SUM(CASE WHEN itt.effect = "subtraction" THEN ABS(iti.qty) ELSE 0 END) as total_outgoing_qty
            ')
            ->first();

        $totalIncomingQty = (float) ($result->total_incoming_qty ?? 0);
        $totalIncomingCost = (float) ($result->total_incoming_cost ?? 0);

        if ($totalIncomingQty <= 0) {
            return $this->getFallbackCost($itemId);
        }

        return $totalIncomingCost / $totalIncomingQty;
    }

    /**
     * Calculate COGS for a sales invoice item using Moving Average.
     *
     * @param int $itemId
     * @param float $qty Sold quantity
     * @param int|null $warehouseId
     * @param string $date
     * @return array ['unit_cost' => float, 'total_cost' => float]
     */
    public function calculateSaleCost(int $itemId, float $qty, ?int $warehouseId, string $date): array
    {
        $unitCost = $this->getCostAtDate($itemId, $warehouseId, $date);
        $totalCost = round($qty * $unitCost, 4);

        return [
            'unit_cost' => round($unitCost, 4),
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Update item_units purchase_price based on the latest moving average.
     * This keeps the catalog price in sync with the actual cost.
     *
     * @param int $itemId
     * @return void
     */
    public function updateItemUnitCost(int $itemId): void
    {
        $avgCost = $this->getCurrentAverageCost($itemId);

        if ($avgCost > 0) {
            ItemUnit::where('item_id', $itemId)
                ->where('is_default', true)
                ->update(['purchase_price' => round($avgCost, 2)]);
        }
    }

    /**
     * Get the profit for a sales invoice item.
     *
     * @param float $sellingPrice
     * @param float $unitCost
     * @param float $qty
     * @return array ['profit' => float, 'margin' => float]
     */
    public function calculateProfit(float $sellingPrice, float $unitCost, float $qty): array
    {
        $profit = ($sellingPrice - $unitCost) * $qty;
        $margin = $sellingPrice > 0 ? (($sellingPrice - $unitCost) / $sellingPrice) * 100 : 0;

        return [
            'profit' => round($profit, 2),
            'margin' => round($margin, 2),
        ];
    }

    /**
     * Fallback cost: try item_units.purchase_price, then 0
     */
    private function getFallbackCost(int $itemId): float
    {
        $unit = ItemUnit::where('item_id', $itemId)
            ->where('is_default', true)
            ->first();

        return $unit ? (float) $unit->purchase_price : 0;
    }

    /**
     * Recalculate all sales invoice items costs based on Moving Average.
     * Used by the artisan command to fix historical data.
     *
     * @param int|null $companyId
     * @return array ['updated' => int, 'total' => int]
     */
    public function recalculateAllSalesCosts(?int $companyId = null): array
    {
        $query = DB::table('sales_invoices as si')
            ->join('sales_invoice_items as sii', 'si.id', '=', 'sii.sales_invoice_id')
            ->where('si.status', '!=', 'cancelled')
            ->whereNull('si.deleted_at')
            ->whereNull('sii.deleted_at')
            ->select('sii.id', 'sii.item_id', 'sii.qty', 'si.warehouse_id', 'si.invoice_date');

        if ($companyId) {
            $query->where('si.company_id', $companyId);
        }

        $items = $query->get();
        $updated = 0;

        foreach ($items as $item) {
            $cost = $this->calculateSaleCost(
                $item->item_id,
                abs((float) $item->qty),
                $item->warehouse_id,
                $item->invoice_date
            );

            DB::table('sales_invoice_items')
                ->where('id', $item->id)
                ->update([
                    'unit_cost' => $cost['unit_cost'],
                    'total_cost' => $cost['total_cost'],
                ]);

            $updated++;
        }

        return ['updated' => $updated, 'total' => $items->count()];
    }
}
