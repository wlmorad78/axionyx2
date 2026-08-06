<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use App\Models\Treasury;
use App\Models\TreasuryType;
use Illuminate\Database\Seeder;

class WarehouseTreasurySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $branches = Branch::where('company_id', $company->id)->get();
            $mainBranch = $branches->first();

            // Warehouses
            $warehouseTypes = WarehouseType::all();
            $mainWH = WarehouseType::where('code', 'MAIN')->first();
            $returnWH = WarehouseType::where('code', 'RETURN')->first();
            $damagedWH = WarehouseType::where('code', 'DAMAGED')->first();
            $vehicleWH = WarehouseType::where('code', 'VEHICLE')->first();

            $warehouses = [
                ['code' => 'WH-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001', 'name' => 'المخزن الرئيسي', 'branch_id' => $mainBranch->id, 'warehouse_type_id' => $mainWH?->id, 'is_default' => true],
                ['code' => 'WH-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-002', 'name' => 'مخزن المرتجعات', 'branch_id' => $mainBranch->id, 'warehouse_type_id' => $returnWH?->id, 'is_default' => false],
                ['code' => 'WH-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-003', 'name' => 'مخزن التاليف', 'branch_id' => $mainBranch->id, 'warehouse_type_id' => $damagedWH?->id, 'is_default' => false],
            ];

            if ($branches->count() > 1) {
                $secondBranch = $branches->skip(1)->first();
                $warehouses[] = [
                    'code' => 'WH-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-004',
                    'name' => 'مخزن الفرع الثاني',
                    'branch_id' => $secondBranch->id,
                    'warehouse_type_id' => $mainWH?->id,
                    'is_default' => false,
                ];
            }

            foreach ($warehouses as $w) {
                Warehouse::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $w['code']],
                    array_merge($w, ['company_id' => $company->id, 'type' => 'main', 'is_active' => true])
                );
            }

            // Treasuries
            $revenueTreasury = TreasuryType::where('code', 'REVENUE')->first();
            $expenseTreasury = TreasuryType::where('code', 'EXPENSE')->first();
            $otherTreasury = TreasuryType::where('code', 'OTHER')->first();

            $treasuries = [
                ['code' => 'TR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001', 'name' => 'خزينة الإيرادات', 'branch_id' => $mainBranch->id, 'treasury_type_id' => $revenueTreasury?->id, 'opening_balance' => 50000, 'is_default' => true],
                ['code' => 'TR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-002', 'name' => 'خزينة المصروفات', 'branch_id' => $mainBranch->id, 'treasury_type_id' => $expenseTreasury?->id, 'opening_balance' => 20000, 'is_default' => false],
                ['code' => 'TR-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-003', 'name' => 'خزينة أخرى', 'branch_id' => $mainBranch->id, 'treasury_type_id' => $otherTreasury?->id, 'opening_balance' => 10000, 'is_default' => false],
            ];

            foreach ($treasuries as $t) {
                Treasury::updateOrCreate(
                    ['company_id' => $company->id, 'code' => $t['code']],
                    array_merge($t, ['company_id' => $company->id, 'is_active' => true])
                );
            }
        }
    }
}
