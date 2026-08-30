<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\User;
use App\Models\UserType;

class DefaultUserTypesSeeder extends Seeder
{
    /**
     * أنواع المستخدمين الافتراضية (الأسماء تطابق ثوابت RoleNames ليعمل hasRole).
     */
    protected array $typeDefs = [
        'Owner' => true,
        'Admin' => true,
        'Accountant' => false,
        'HR Manager' => false,
        'Sales Representative' => false,
        'SalesMan' => false,
        'Warehouse Keeper' => false,
        'Cashier' => false,
        'Branch Manager' => false,
        'Sales Manager' => false,
        'Distribution Manager' => false,
        'Sales Supervisor' => false,
    ];

    public function run(): void
    {
        // نطاقات الشركات (user_types.company_id غير قابل لـ NULL)
        $companyIds = Company::pluck('id')->toArray();

        $map = []; // [name => [company_id => user_type_id]]

        foreach ($companyIds as $companyId) {
            foreach ($this->typeDefs as $name => $protected) {
                $code = strtolower(str_replace(' ', '_', $name));
                $type = UserType::firstOrCreate(
                    ['company_id' => $companyId, 'code' => $code],
                    [
                        'name_ar' => $name,
                        'name_en' => $name,
                        'description' => $name . ' user type',
                        'is_active' => true,
                        'is_protected' => $protected,
                    ]
                );
                $map[$name][$companyId] = $type->id;
            }
        }

        // ربط المستخدمين الحاليين بأنواعهم عبر وسيط user_roles الموجود
        $userRoles = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->select('user_roles.user_id', 'roles.name')
            ->get();

        $byUser = [];
        foreach ($userRoles as $ur) {
            if (!isset($byUser[$ur->user_id])) {
                $byUser[$ur->user_id] = $ur->name; // أول دور
            }
        }

        $updated = 0;
        foreach (User::withTrashed()->get() as $user) {
            $typeId = null;

            if (isset($byUser[$user->id])) {
                $roleName = $byUser[$user->id];
                $typeId = $map[$roleName][$user->company_id]
                    ?? $map[$roleName][null]
                    ?? null;
            }

            // المشرف الأعلى (بلا شركة) بدون دور → Owner عام
            if ($typeId === null && $user->company_id === null) {
                $typeId = $map['Owner'][null] ?? null;
            }

            if ($typeId !== null && $user->user_type_id !== $typeId) {
                $user->user_type_id = $typeId;
                $user->save();
                $updated++;
            }
        }

        $this->command?->info("تم إنشاء أنواع المستخدمين وربط {$updated} مستخدمًا بنوعه.");
    }
}
