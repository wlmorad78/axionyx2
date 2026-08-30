<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetCategory;
use App\Models\AssetDepreciation;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleDailyExpense;
use App\Models\VehicleFuelTransaction;
use App\Models\VehicleLoading;
use App\Models\VehicleMaintenance;
use App\Models\VehicleType;
use App\Models\User;
use Illuminate\Database\Seeder;

class FleetFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $adminUser = User::where('company_id', $company->id)->first();
            $vehicleTypes = VehicleType::all();

            // Vehicles
            $vehiclesData = [
                ['plate_number' => 'أ ب ج-' . str_pad($company->id * 100 + 1, 4, '0', STR_PAD_LEFT), 'vehicle_code' => 'VH-' . str_pad($company->id * 100 + 1, 4, '0', STR_PAD_LEFT), 'model' => 'Hyundai H100', 'year' => 2023],
                ['plate_number' => 'د ه و-' . str_pad($company->id * 100 + 2, 4, '0', STR_PAD_LEFT), 'vehicle_code' => 'VH-' . str_pad($company->id * 100 + 2, 4, '0', STR_PAD_LEFT), 'model' => 'Isuzu NPR', 'year' => 2022],
                ['plate_number' => 'ز ح ط-' . str_pad($company->id * 100 + 3, 4, '0', STR_PAD_LEFT), 'vehicle_code' => 'VH-' . str_pad($company->id * 100 + 3, 4, '0', STR_PAD_LEFT), 'model' => 'Toyota Hiace', 'year' => 2024],
            ];

            $vehicleModels = [];
            foreach ($vehiclesData as $i => $v) {
                $vehicleModels[] = Vehicle::updateOrCreate(
                    ['company_id' => $company->id, 'vehicle_code' => $v['vehicle_code']],
                    [
                        'plate_number' => $v['plate_number'],
                        'vehicle_type_id' => $vehicleTypes[$i % $vehicleTypes->count()]?->id,
                        'model' => $v['model'],
                        'year' => $v['year'],
                        'status' => 'active',
                    ]
                );
            }

            // Drivers
            $employees = \App\Models\Employee::where('company_id', $company->id)->take(2)->get();
            $driverModels = [];
            foreach ($employees as $i => $employee) {
                $driverModels[] = Driver::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'license_no' => 'DL-' . str_pad($company->id * 100 + $i + 1, 6, '0', STR_PAD_LEFT),
                        'license_expiry' => now()->addYears(2)->toDateString(),
                        'mobile' => '0101234567' . $i,
                        'status' => 'active',
                    ]
                );
            }

            // Vehicle Assignments
            foreach ($vehicleModels as $i => $vehicle) {
                if (isset($driverModels[$i % count($driverModels)])) {
                    VehicleAssignment::updateOrCreate(
                        ['vehicle_id' => $vehicle->id, 'driver_id' => $driverModels[$i % count($driverModels)]->id],
                        ['from_date' => '2026-01-01', 'status' => 'active']
                    );
                }
            }

            // Vehicle Fuel Transactions
            foreach ($vehicleModels as $i => $vehicle) {
                VehicleFuelTransaction::create([
                    'vehicle_id' => $vehicle->id,
                    'transaction_date' => now()->subDays($i * 2)->toDateString(),
                    'odometer' => 50000 + ($i * 1000),
                    'fuel_qty' => 50,
                    'fuel_cost' => 625,
                ]);
            }

            // Vehicle Maintenance
            VehicleMaintenance::create([
                'vehicle_id' => $vehicleModels[0]?->id,
                'maintenance_date' => now()->subDays(15)->toDateString(),
                'maintenance_type' => 'preventive',
                'cost' => 500,
                'notes' => 'تغيير زيت المحرك',
            ]);

            // Vehicle Daily Expenses
            foreach ($vehicleModels as $i => $vehicle) {
                VehicleDailyExpense::create([
                    'vehicle_id' => $vehicle->id,
                    'expense_date' => now()->subDays($i)->toDateString(),
                    'expense_type' => 'OTHER',
                    'amount' => 300 + ($i * 100),
                    'notes' => 'صيانة دورية',
                    'created_by' => $adminUser?->id,
                ]);
            }

            // Vehicle Loading
            if ($vehicleModels) {
                VehicleLoading::create([
                    'vehicle_id' => $vehicleModels[0]?->id,
                    'loading_date' => now()->toDateString(),
                    'loaded_value' => 5000,
                ]);
            }

            // Asset Categories
            $assetCategories = [
                ['code' => 'AC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-01', 'name' => 'أجهزة كمبيوتر'],
                ['code' => 'AC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-02', 'name' => 'أثاث مكتبي'],
                ['code' => 'AC-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-03', 'name' => 'سيارات'],
            ];

            $acModels = [];
            foreach ($assetCategories as $ac) {
                $acModels[] = AssetCategory::updateOrCreate(
                    ['code' => $ac['code']],
                    ['name' => $ac['name']]
                );
            }

            // Assets
            $assetsData = [
                ['asset_code' => 'ASSET-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001', 'asset_name' => 'كمبيوتر مكتبي 1', 'cat' => 0, 'purchase_date' => '2025-01-15', 'purchase_cost' => 15000],
                ['asset_code' => 'ASSET-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-002', 'asset_name' => 'طاولة مكتبية', 'cat' => 1, 'purchase_date' => '2025-01-15', 'purchase_cost' => 5000],
                ['asset_code' => 'ASSET-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-003', 'asset_name' => 'شاشة كمبيوتر', 'cat' => 0, 'purchase_date' => '2025-01-15', 'purchase_cost' => 8000],
            ];

            $assetModels = [];
            foreach ($assetsData as $a) {
                $assetModels[] = Asset::updateOrCreate(
                    ['asset_code' => $a['asset_code']],
                    [
                        'asset_category_id' => $acModels[$a['cat']]?->id ?? $acModels[0]?->id,
                        'asset_name' => $a['asset_name'],
                        'purchase_date' => $a['purchase_date'],
                        'purchase_cost' => $a['purchase_cost'],
                        'current_value' => $a['purchase_cost'] * 0.8,
                        'status' => 'active',
                    ]
                );
            }

            // Asset Assignments
            if ($assetModels && $employees->isNotEmpty()) {
                AssetAssignment::create([
                    'asset_id' => $assetModels[0]?->id,
                    'employee_id' => $employees[0]->id,
                    'assigned_date' => '2025-01-15',
                    'status' => 'assigned',
                ]);
            }

            // Asset Depreciations
            foreach ($assetModels as $asset) {
                AssetDepreciation::create([
                    'asset_id' => $asset->id,
                    'depreciation_date' => now()->toDateString(),
                    'amount' => $asset->purchase_cost / 60,
                ]);
            }
        }
    }
}
