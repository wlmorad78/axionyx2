<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('permissions');
        $definitions = $config['definitions'] ?? [];

        foreach ($definitions as $code => $descriptionAr) {
            Permission::updateOrCreate(['code' => $code], ['name' => $descriptionAr]);
        }

        $companies = Company::all();
        $allPermissions = Permission::pluck('id')->toArray();

        $globalAdmin = Role::where('name', RoleNames::ADMIN)->whereNull('company_id')->first();
        if ($globalAdmin) {
            $globalAdmin->permissions()->sync($allPermissions);
        }

        foreach ($companies as $company) {
            $roles = [
                ['name' => RoleNames::ADMIN, 'code' => 'admin_' . $company->id, 'permissions' => $allPermissions],
                ['name' => RoleNames::BRANCH_MANAGER, 'code' => 'branch_manager_' . $company->id, 'permissions' => $this->getPermissionsByRole('branch_manager')],
                ['name' => RoleNames::ACCOUNTANT, 'code' => 'accountant_' . $company->id, 'permissions' => $this->getPermissionsByRole('accountant')],
                ['name' => RoleNames::WAREHOUSE_KEEPER, 'code' => 'warehouse_keeper_' . $company->id, 'permissions' => $this->getPermissionsByRole('warehouse_keeper')],
                ['name' => RoleNames::SALES_REP, 'code' => 'sales_rep_' . $company->id, 'permissions' => $this->getPermissionsByRole('sales_rep')],
                ['name' => RoleNames::SALES_MAN, 'code' => 'sales_man_' . $company->id, 'permissions' => $this->getPermissionsByRole('sales_rep')],
                ['name' => RoleNames::SALES_MANAGER, 'code' => 'sales_manager_' . $company->id, 'permissions' => $this->getPermissionsByRole('sales_manager')],
                ['name' => RoleNames::SALES_SUPERVISOR, 'code' => 'sales_supervisor_' . $company->id, 'permissions' => $this->getPermissionsByRole('sales_manager')],
                ['name' => RoleNames::DISTRIBUTION_MANAGER, 'code' => 'distribution_manager_' . $company->id, 'permissions' => $this->getPermissionsByRole('distribution_manager')],
                ['name' => RoleNames::CASHIER, 'code' => 'cashier_' . $company->id, 'permissions' => $this->getPermissionsByRole('cashier')],
                ['name' => RoleNames::HR_MANAGER, 'code' => 'hr_manager_' . $company->id, 'permissions' => $this->getPermissionsByRole('hr_manager')],
            ];

            foreach ($roles as $r) {
                $role = Role::firstOrCreate(
                    ['name' => $r['name'], 'company_id' => $company->id],
                    ['code' => $r['code']]
                );
                $role->permissions()->sync($r['permissions']);
            }
        }
    }

    private function getPermissionsByRole(string $roleName): array
    {
        $defaults = config('permissions.defaults.' . $roleName, []);
        $permissionIds = [];

        foreach ($defaults as $pattern) {
            $matched = Permission::where(function ($query) use ($pattern) {
                if ($pattern === '*') {
                    $query->whereRaw('1=1');
                } elseif (str_ends_with($pattern, '.*')) {
                    $prefix = rtrim($pattern, '.*');
                    $query->where('code', 'like', $prefix . '.%');
                } else {
                    $query->where('code', $pattern);
                }
            })->pluck('id')->toArray();

            $permissionIds = array_merge($permissionIds, $matched);
        }

        return array_unique($permissionIds);
    }
}
