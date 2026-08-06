<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Route;
use App\Models\RouteSchedule;
use App\Models\RouteCustomer;
use App\Models\CustomerVisit;
use App\Models\SalesTerritory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DistributionSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $branch = Branch::where('company_id', $company->id)->first();
            $territory = SalesTerritory::where('company_id', $company->id)->first();
            $customers = Customer::where('company_id', $company->id)->take(5)->get();
            $employees = Employee::where('company_id', $company->id)->take(3)->get();

            if ($customers->isEmpty() || $employees->isEmpty()) continue;

            // Routes
            $route = Route::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'RT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'branch_id' => $branch?->id,
                    'sales_territory_id' => $territory?->id,
                    'name_ar' => 'خط السير الرئيسي',
                    'name_en' => 'Main Route',
                    'description' => 'خط السير الرئيسي للتوزيع',
                    'is_active' => true,
                ]
            );

            $route2 = Route::updateOrCreate(
                ['company_id' => $company->id, 'code' => 'RT-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-002'],
                [
                    'branch_id' => $branch?->id,
                    'sales_territory_id' => $territory?->id,
                    'name_ar' => 'خط السير الثانوي',
                    'name_en' => 'Secondary Route',
                    'description' => 'خط سير ثانوي',
                    'is_active' => true,
                ]
            );

            // Route Schedules
            $days = ['1', '2', '3', '4', '5']; // Saturday=1, Sunday=2, Monday=3, Tuesday=4, Wednesday=5
            foreach ($days as $i => $day) {
                if ($employees->has($i % $employees->count())) {
                    RouteSchedule::updateOrCreate(
                        ['route_id' => $route->id, 'employee_id' => $employees[$i % $employees->count()]->id, 'day_of_week' => $day],
                        ['visit_order' => $i + 1, 'is_active' => true]
                    );
                }
            }

            // Route Customers
            foreach ($customers as $i => $customer) {
                RouteCustomer::updateOrCreate(
                    ['route_id' => $route->id, 'customer_id' => $customer->id],
                    [
                        'visit_order' => $i + 1,
                        'visit_frequency' => 'Daily',
                        'is_mandatory' => true,
                        'is_active' => true,
                    ]
                );
            }

            // Customer Visits
            $today = now()->toDateString();
            foreach ($customers->take(3) as $i => $customer) {
                CustomerVisit::updateOrCreate(
                    ['employee_id' => $employees[0]->id, 'customer_id' => $customer->id, 'visit_date' => $today],
                    [
                        'route_id' => $route->id,
                        'check_in_time' => '09:' . str_pad($i * 15, 2, '0', STR_PAD_LEFT) . ':00',
                        'check_out_time' => '09:' . str_pad($i * 15 + 10, 2, '0', STR_PAD_LEFT) . ':00',
                        'latitude' => 31.2 + ($i * 0.005),
                        'longitude' => 29.9 + ($i * 0.005),
                        'visit_status' => 'completed',
                        'notes' => 'زيارة ناجحة',
                    ]
                );
            }
        }
    }
}
