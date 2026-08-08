<?php

use Illuminate\Support\Facades\Route;
use App\Models\Customer;
use App\Models\RouteCustomer;
use App\Models\RouteSchedule;
use App\Models\RouteAssignment;
use App\Models\Employee;
use App\Models\Representative;
use App\Support\DayOfWeekHelper;
use Illuminate\Support\Facades\DB;

if (!function_exists('calculateNewHandheldBalance')) {
    function calculateNewHandheldBalance($customerId, $companyId) {
        $allInvoices = \App\Models\Sales\SalesInvoice::where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(net_total), 0) as total_invoiced, COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();

        $collectionsBalance = \App\Models\Sales\Collection::where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_collections')
            ->first();

        $invoiceBalance = (float) $allInvoices->total_paid - (float) $allInvoices->total_invoiced;
        $collectionsEffect = -1 * (float) $collectionsBalance->total_collections;

        return round($invoiceBalance + $collectionsEffect, 2);
    }
}

if (!function_exists('resolveNewHandheldEmployee')) {
    function resolveNewHandheldEmployee(\Illuminate\Http\Request $request) {
        $user = $request->user();
        if (!$user) return null;

        $employee = Employee::where('email', $user->email)->first();

        if (!$employee) {
            $representative = Representative::where('user_id', $user->id)->first();
            if ($representative) {
                $employee = Employee::where('national_id', $representative->code)->first();
            }
        }

        return $employee;
    }
}

Route::middleware('auth:sanctum')->group(function () {
    Route::get('new-handheld/customers', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    $todayNumber = DayOfWeekHelper::todayNumber();

    $employee = resolveNewHandheldEmployee($request);
    $delegatePhone = $employee?->mobile ?? $employee?->phone ?? null;

    $supervisorPhone = null;
    if ($employee) {
        $salesmanAssignment = DB::table('salesman_assignments')
            ->where('employee_id', $employee->id)
            ->where('job_role', 'salesman')
            ->where('is_active', true)
            ->first();

        if ($salesmanAssignment && $salesmanAssignment->parent_assignment_id) {
            $supervisorAssignment = DB::table('salesman_assignments')
                ->where('id', $salesmanAssignment->parent_assignment_id)
                ->first();
            if ($supervisorAssignment) {
                $supervisor = Employee::where('id', $supervisorAssignment->employee_id)->first();
                $supervisorPhone = $supervisor?->mobile ?? $supervisor?->phone ?? null;
            }
        }
    }

    $employeeRouteIds = collect();
    if ($employee) {
        $scheduleRouteIds = RouteSchedule::where('is_active', true)
            ->where('employee_id', $employee->id)
            ->pluck('route_id');
        $assignmentRouteIds = RouteAssignment::where('driver_id', $employee->id)
            ->whereDate('assignment_date', now()->toDateString())
            ->where('is_active', true)
            ->pluck('route_id');
        $employeeRouteIds = $scheduleRouteIds->merge($assignmentRouteIds)->unique();
    }

    $routeCustomers = RouteCustomer::where('is_active', true)
        ->whereHas('customer', fn($q) => $q->where('company_id', $user->company_id));

    if ($employeeRouteIds->isNotEmpty()) {
        $routeCustomers = $routeCustomers->whereIn('route_id', $employeeRouteIds);
    }

    $routeCustomers = $routeCustomers
        ->with(['customer', 'route', 'route.salesTerritory'])
        ->orderBy('visit_order')
        ->get()
        ->filter(fn($rc) => $rc->customer && !$rc->customer->trashed());

    if ($routeCustomers->isEmpty()) {
        $customers = Customer::where('is_active', true)
            ->where('company_id', $user->company_id)
            ->orderBy('name_ar')
            ->get();

        return response()->json([
            'data' => $customers->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name_ar' => $c->name_ar,
                'name_en' => $c->name_en,
                'phone' => $c->phone,
                'mobile' => $c->mobile,
                'tax_number' => $c->tax_number ?: $c->national_id,
                'national_id' => $c->national_id,
                'address_line' => $c->address_line,
                'governorate' => $c->governorate?->name_ar,
                'city' => $c->city?->name_ar,
                'area' => $c->area?->name_ar,
                'balance' => calculateNewHandheldBalance($c->id, $user->company_id),
                'visit_order' => null,
                'visit_frequency' => null,
                'day_of_week' => null,
                'is_today' => true,
                'is_active' => true,
                'delegate_phone' => $delegatePhone,
                'supervisor_phone' => $supervisorPhone,
                'sales_territory_id' => null,
                'sales_territory_name' => null,
            ])->values(),
            'today_count' => $customers->count(),
            'other_count' => 0,
        ]);
    }

    $todayRouteIds = RouteSchedule::where('is_active', true)
        ->whereRaw("INSTR(',' || day_of_week || ',', ',' || ? || ',') > 0", [$todayNumber]);
    if ($employeeRouteIds->isNotEmpty()) {
        $todayRouteIds = $todayRouteIds->whereIn('route_id', $employeeRouteIds);
    }
    $todayRouteIds = $todayRouteIds->pluck('route_id');

    $data = [];
    foreach ($routeCustomers as $rc) {
        $isToday = $todayRouteIds->contains($rc->route_id);
        $schedule = RouteSchedule::where('route_id', $rc->route_id)->where('is_active', true)->first();

        $data[] = [
            'id' => $rc->customer->id,
            'code' => $rc->customer->code,
            'name_ar' => $rc->customer->name_ar,
            'name_en' => $rc->customer->name_en,
            'phone' => $rc->customer->phone,
            'mobile' => $rc->customer->mobile,
            'tax_number' => $rc->customer->tax_number ?: $rc->customer->national_id,
            'national_id' => $rc->customer->national_id,
            'address_line' => $rc->customer->address_line,
            'governorate' => $rc->customer->governorate?->name_ar,
            'city' => $rc->customer->city?->name_ar,
            'area' => $rc->customer->area?->name_ar,
            'visit_order' => $rc->visit_order,
            'visit_frequency' => $rc->visit_frequency,
            'day_of_week' => $schedule?->day_of_week,
            'route_id' => $rc->route_id,
            'is_today' => $isToday,
            'is_active' => $rc->is_active,
            'delegate_phone' => $delegatePhone,
            'supervisor_phone' => $supervisorPhone,
            'sales_territory_id' => $rc->route?->sales_territory_id,
            'sales_territory_name' => $rc->route?->salesTerritory?->name_ar,
            'balance' => calculateNewHandheldBalance($rc->customer->id, $user->company_id),
        ];
    }

    $todayCount = count(array_filter($data, fn($d) => $d['is_today']));
    $otherCount = count($data) - $todayCount;

    return response()->json([
        'data' => $data,
        'today_count' => $todayCount,
        'other_count' => $otherCount,
    ]);
});
});
