<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCashAccount;
use App\Models\VehicleCashTransaction;
use App\Models\VehicleDailyExpense;
use App\Models\VehicleDeposit;
use App\Models\VehicleLoad;
use App\Models\VehicleLoadItem;
use App\Models\VehicleSettlement;
use App\Models\VehicleSettlementItem;
use App\Models\VehicleStockBalance;
use App\Models\VehicleStockCount;
use App\Models\VehicleStockCountItem;
use App\Models\VehicleUnload;
use App\Models\VehicleUnloadItem;
use App\Models\VehicleWarehouse;
use Illuminate\Database\Seeder;

class VehicleInventoryFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $vehicle = Vehicle::where('company_id', $company->id)->first();
            $items = Item::where('company_id', $company->id)->take(3)->get();
            $adminUser = User::where('company_id', $company->id)->first();

            if (!$vehicle || $items->isEmpty()) continue;

            // Vehicle Warehouses
            $vw = VehicleWarehouse::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'VW-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'vehicle_id' => $vehicle->id,
                    'is_active' => true,
                ]
            );

            // Vehicle Stock Balances
            foreach ($items as $item) {
                VehicleStockBalance::updateOrCreate(
                    ['vehicle_warehouse_id' => $vw->id, 'item_id' => $item->id],
                    [
                        'qty' => 50,
                        'average_cost' => 50,
                        'stock_value' => 2500,
                    ]
                );
            }

            // Vehicle Loads
            $load = VehicleLoad::updateOrCreate(
                ['load_no' => 'VL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'company_id' => $company->id,
                    'vehicle_id' => $vehicle->id,
                    'load_date' => now()->toDateString(),
                    'created_by' => $adminUser?->id,
                ]
            );

            foreach ($items->take(2) as $item) {
                VehicleLoadItem::create([
                    'vehicle_load_id' => $load->id,
                    'item_id' => $item->id,
                    'qty' => 20,
                    'cost' => 50,
                ]);
            }

            // Vehicle Unloads
            $unload = VehicleUnload::updateOrCreate(
                ['unload_no' => 'VU-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'vehicle_id' => $vehicle->id,
                    'unload_date' => now()->toDateString(),
                    'notes' => 'تفريغ بعد التوزيع',
                ]
            );

            foreach ($items->take(2) as $item) {
                VehicleUnloadItem::create([
                    'vehicle_unload_id' => $unload->id,
                    'item_id' => $item->id,
                    'qty' => 15,
                    'cost' => 50,
                ]);
            }

            // Vehicle Cash Accounts
            $vca = VehicleCashAccount::updateOrCreate(
                ['vehicle_id' => $vehicle->id],
                ['opening_balance' => 5000, 'current_balance' => 8000]
            );

            // Vehicle Cash Transactions
            VehicleCashTransaction::create([
                'vehicle_cash_account_id' => $vca->id,
                'transaction_date' => now()->toDateString(),
                'transaction_type' => 'COLLECTION',
                'amount' => 3000,
                'notes' => 'تحصيل من عملاء',
                'reference_type' => 'Collection',
                'reference_id' => 1,
            ]);

            VehicleCashTransaction::create([
                'vehicle_cash_account_id' => $vca->id,
                'transaction_date' => now()->toDateString(),
                'transaction_type' => 'EXPENSE',
                'amount' => 500,
                'notes' => 'مصروفات وقود',
                'reference_type' => 'VehicleDailyExpense',
                'reference_id' => 1,
            ]);

            // Vehicle Daily Expenses
            VehicleDailyExpense::create([
                'vehicle_id' => $vehicle->id,
                'expense_date' => now()->toDateString(),
                'expense_type' => 'FUEL',
                'amount' => 350,
                'notes' => 'مصروفات يومية',
                'created_by' => $adminUser?->id,
            ]);

            // Vehicle Stock Counts
            $vsc = VehicleStockCount::updateOrCreate(
                ['count_no' => 'VSC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'vehicle_id' => $vehicle->id,
                    'count_date' => now()->toDateString(),
                    'status' => 'COMPLETED',
                ]
            );

            foreach ($items->take(2) as $item) {
                VehicleStockCountItem::create([
                    'vehicle_stock_count_id' => $vsc->id,
                    'item_id' => $item->id,
                    'system_qty' => 50,
                    'actual_qty' => 48,
                    'variance_qty' => -2,
                ]);
            }

            // Vehicle Settlements
            $vs = VehicleSettlement::updateOrCreate(
                ['settlement_no' => 'VST-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'vehicle_id' => $vehicle->id,
                    'settlement_date' => now()->toDateString(),
                    'sales_value' => 15000,
                    'expense_value' => 2000,
                    'cash_difference' => 13000,
                    'status' => 'DRAFT',
                ]
            );

            VehicleSettlementItem::create([
                'vehicle_settlement_id' => $vs->id,
                'item_id' => $items[0]->id,
                'opening_qty' => 50,
                'loaded_qty' => 20,
                'sold_qty' => 15,
                'returned_qty' => 0,
                'closing_qty' => 55,
                'variance_qty' => 0,
            ]);

            // Vehicle Deposits
            VehicleDeposit::create([
                'vehicle_id' => $vehicle->id,
                'deposit_no' => 'VD-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001',
                'deposit_date' => now()->toDateString(),
                'amount' => 10000,
                'notes' => 'إيداع نقدي',
            ]);
        }
    }
}
