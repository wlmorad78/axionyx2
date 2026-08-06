<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\InventoryOpeningBalance;
use App\Models\InventoryRevaluation;
use App\Models\InventoryRevaluationItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\InventoryTransactionType;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Database\Seeder;

class InventoryFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $items = Item::where('company_id', $company->id)->take(5)->get();
            $warehouse = Warehouse::where('company_id', $company->id)->first();
            $adminUser = User::where('company_id', $company->id)->first();

            if ($items->isEmpty() || !$warehouse) continue;

            $openingType = InventoryTransactionType::where('code', 'OPENING_BALANCE')->first();
            $purchaseType = InventoryTransactionType::where('code', 'PURCHASE_RECEIPT')->first();
            $salesType = InventoryTransactionType::where('code', 'SALES_INVOICE')->first();

            // Inventory Opening Balances
            foreach ($items as $item) {
                InventoryOpeningBalance::updateOrCreate(
                    ['item_id' => $item->id, 'warehouse_id' => $warehouse->id],
                    [
                        'company_id' => $company->id,
                        'qty' => 100,
                        'unit_cost' => 50,
                        'total_cost' => 5000,
                        'opening_date' => '2026-01-01',
                        'unit_id' => 1,
                    ]
                );
            }

            // Inventory Transactions
            for ($i = 0; $i < 3; $i++) {
                $transaction = InventoryTransaction::updateOrCreate(
                    ['company_id' => $company->id, 'transaction_no' => 'INV-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT)],
                    [
                        'transaction_type_id' => [$openingType, $purchaseType, $salesType][$i]?->id,
                        'warehouse_id' => $warehouse->id,
                        'transaction_date' => now()->subDays(10 - $i * 3)->toDateString(),
                        'transaction_time' => now()->format('H:i:s'),
                        'reference_type' => ['OpeningBalance', 'PurchaseReceipt', 'SalesInvoice'][$i],
                        'reference_id' => $i + 1,
                        'status' => 'posted',
                    ]
                );

                foreach ($items->take(2) as $item) {
                    InventoryTransactionItem::create([
                        'inventory_transaction_id' => $transaction->id,
                        'item_id' => $item->id,
                        'unit_id' => 1,
                        'qty' => $i === 2 ? -10 : 50,
                        'unit_cost' => 50,
                        'total_cost' => $i === 2 ? -500 : 2500,
                    ]);
                }
            }

            // Stock Adjustments
            $adjustment = StockAdjustment::updateOrCreate(
                ['company_id' => $company->id, 'adjustment_no' => 'SA-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'warehouse_id' => $warehouse->id,
                    'adjustment_date' => now()->subDays(5)->toDateString(),
                    'reason' => 'تسوية جرد',
                    'status' => 'posted',
                    'created_by' => $adminUser?->id,
                ]
            );

            foreach ($items->take(2) as $item) {
                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'item_id' => $item->id,
                    'unit_id' => 1,
                    'system_qty' => 95,
                    'actual_qty' => 100,
                    'difference_qty' => 5,
                    'unit_cost' => 50,
                    'difference_value' => 250,
                ]);
            }

            // Stock Counts
            $count = StockCount::updateOrCreate(
                ['company_id' => $company->id, 'count_no' => 'SC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'warehouse_id' => $warehouse->id,
                    'count_date' => now()->subDays(3)->toDateString(),
                    'status' => 'posted',
                    'notes' => 'جرد شهري',
                ]
            );

            foreach ($items->take(3) as $item) {
                StockCountItem::create([
                    'stock_count_id' => $count->id,
                    'item_id' => $item->id,
                    'unit_id' => 1,
                    'system_qty' => 100,
                    'counted_qty' => 98,
                    'variance_qty' => -2,
                ]);
            }

            // Warehouse Transfers
            $secondWarehouse = Warehouse::where('company_id', $company->id)->skip(1)->first();
            if ($secondWarehouse) {
                $transfer = WarehouseTransfer::updateOrCreate(
                    ['company_id' => $company->id, 'transfer_no' => 'WT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    [
                        'from_warehouse_id' => $warehouse->id,
                        'to_warehouse_id' => $secondWarehouse->id,
                        'transfer_date' => now()->subDays(2)->toDateString(),
                        'status' => 'posted',
                        'notes' => 'تحويل بين المخازن',
                    ]
                );

                foreach ($items->take(2) as $item) {
                    WarehouseTransferItem::create([
                        'warehouse_transfer_id' => $transfer->id,
                        'item_id' => $item->id,
                        'unit_id' => 1,
                        'qty' => 10,
                    ]);
                }
            }

            // Item Batches
            foreach ($items->take(3) as $item) {
                ItemBatch::updateOrCreate(
                    ['item_id' => $item->id, 'batch_no' => 'BATCH-' . $item->id . '-001'],
                    [
                        'production_date' => now()->subDays(30)->toDateString(),
                        'expiry_date' => now()->addDays(365)->toDateString(),
                        'purchase_price' => 50,
                        'qty' => 50,
                        'remaining_qty' => 50,
                    ]
                );
            }

            // Inventory Revaluation
            $revaluation = InventoryRevaluation::updateOrCreate(
                ['company_id' => $company->id, 'revaluation_no' => 'IR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'warehouse_id' => $warehouse->id,
                    'revaluation_date' => now()->toDateString(),
                    'status' => 'posted',
                    'notes' => 'إعادة تقييم المخزون',
                ]
            );

            foreach ($items->take(2) as $item) {
                InventoryRevaluationItem::create([
                    'inventory_revaluation_id' => $revaluation->id,
                    'item_id' => $item->id,
                    'old_cost' => 50,
                    'new_cost' => 55,
                    'difference' => 500,
                ]);
            }
        }
    }
}
