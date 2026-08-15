<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Route;
use App\Models\RouteCustomer;
use App\Models\RouteSchedule;
use App\Models\SalesTerritory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HandheldDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1;
        $branchId = DB::table('branches')->where('company_id', $companyId)->value('id') ?? 5;

        // 1. Handheld user for company 1
        $user = User::updateOrCreate(
            ['usercode' => 7777],
            [
                'name' => 'مندوب الهاند هيلد',
                'password' => Hash::make('123456'),
                'is_active' => true,
                'company_id' => $companyId,
            ]
        );

        DB::table('user_branches')->updateOrInsert(
            ['user_id' => $user->id, 'branch_id' => $branchId],
            ['is_default' => true]
        );

        DB::table('company_user')->updateOrInsert(
            ['user_id' => $user->id, 'company_id' => $companyId]
        );

        // 2. Employee linked to user
        $employee = Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_id' => $companyId,
                'employee_code' => 'EMP-HH-001',
                'first_name_ar' => 'مندوب',
                'last_name_ar' => 'الهاند هيلد',
                'mobile' => '01000000001',
                'email' => 'hh@example.com',
                'is_active' => true,
            ]
        );

        // 3. Sales territories for company 1
        $territories = [];
        foreach (['المنطقة الشمالية', 'المنطقة الجنوبية', 'المنطقة الشرقية'] as $index => $name) {
            $territories[] = SalesTerritory::updateOrCreate(
                ['company_id' => $companyId, 'code' => 'ST-HH-' . ($index + 1)],
                [
                    'branch_id' => $branchId,
                    'sales_territory_type_id' => 1,
                    'name_ar' => $name,
                    'is_active' => true,
                ]
            );
        }

        // 4. Routes
        $routeData = [
            ['code' => 'R-HH-01', 'name_ar' => 'خط سير العامرية'],
            ['code' => 'R-HH-02', 'name_ar' => 'خط سير الحضرة'],
            ['code' => 'R-HH-03', 'name_ar' => 'خط سير السيوف'],
        ];
        $routes = [];
        foreach ($routeData as $index => $data) {
            $routes[] = Route::updateOrCreate(
                ['company_id' => $companyId, 'code' => $data['code']],
                [
                    'branch_id' => $branchId,
                    'sales_territory_id' => $territories[$index % count($territories)]->id,
                    'name_ar' => $data['name_ar'],
                    'is_active' => true,
                ]
            );
        }

        // 5. Customers
        $customerNames = [
            'سوبر ماركت النور',
            'بقالة الأمل',
            'هايبر السلام',
            'مخبز القمة',
            'سوبر ماركت الفردوس',
            'بقالة الرحمة',
            'هايبر البركة',
            'مخبز الذهبية',
            'سوبر ماركت السعادة',
            'بقالة النجمة',
            'هايبر الأنوار',
            'مخبز الزهور',
        ];
        $customers = [];
        foreach ($customerNames as $index => $name) {
            $customers[] = Customer::updateOrCreate(
                ['company_id' => $companyId, 'code' => 'C-HH-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'branch_id' => $branchId,
                    'name_ar' => $name,
                    'phone' => '010' . str_pad($index + 1, 8, '0', STR_PAD_LEFT),
                    'address_line' => 'شارع ' . ($index + 1) . '، المنطقة ' . (($index % 3) + 1),
                    'is_active' => true,
                ]
            );
        }

        // 6. Link customers to routes
        foreach ($routes as $routeIndex => $route) {
            $start = $routeIndex * 4;
            for ($i = 0; $i < 4; $i++) {
                $customer = $customers[$start + $i] ?? null;
                if (!$customer) continue;
                RouteCustomer::updateOrCreate(
                    ['route_id' => $route->id, 'customer_id' => $customer->id],
                    [
                        'visit_order' => $i + 1,
                        'is_active' => true,
                    ]
                );
            }
        }

        // 7. Link employee to routes via schedules
        foreach ($routes as $route) {
            RouteSchedule::updateOrCreate(
                ['route_id' => $route->id, 'employee_id' => $employee->id],
                [
                    'day_of_week' => 1,
                    'is_active' => true,
                ]
            );
        }

        // 8. Items
        $items = [
            ['code' => 'IT-HH-001', 'name_ar' => 'مياه معدنية 1.5 لتر'],
            ['code' => 'IT-HH-002', 'name_ar' => 'عصير برتقال 1 لتر'],
            ['code' => 'IT-HH-003', 'name_ar' => 'بسكويت شاي'],
            ['code' => 'IT-HH-004', 'name_ar' => 'شوكولاتة'],
            ['code' => 'IT-HH-005', 'name_ar' => 'مكرونة 500 جرام'],
            ['code' => 'IT-HH-006', 'name_ar' => 'سكر 1 كجم'],
            ['code' => 'IT-HH-007', 'name_ar' => 'أرز 1 كجم'],
            ['code' => 'IT-HH-008', 'name_ar' => 'زيت طعام 1 لتر'],
            ['code' => 'IT-HH-009', 'name_ar' => 'صابون غسيل'],
            ['code' => 'IT-HH-010', 'name_ar' => 'منظف أرضيات'],
        ];
        foreach ($items as $item) {
            Item::updateOrCreate(
                ['company_id' => $companyId, 'code' => $item['code']],
                [
                    'branch_id' => $branchId,
                    'name_ar' => $item['name_ar'],
                    'is_active' => true,
                ]
            );
        }
    }
}
