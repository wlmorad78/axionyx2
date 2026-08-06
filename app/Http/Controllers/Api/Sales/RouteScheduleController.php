<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\RouteSchedule;
use App\Models\HR\Employee;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteScheduleController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['employee', 'route.salesTerritory'];
        $query = RouteSchedule::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->day_of_week) {
            $query->whereRaw("FIND_IN_SET(?, day_of_week)", [$request->day_of_week]);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
            $query->with(['route' => function ($q) {
                $q->withTrashed();
            }, 'route.salesTerritory' => function ($q) {
                $q->withTrashed();
            }]);
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_schedule', 'store'));

        return response()->json(RouteSchedule::create($data), 201);
    }

    public function show(RouteSchedule $route_schedule)
    {
        return $route_schedule->load([
            'route',
            'employee',
        ]);
    }

    public function update(Request $request, RouteSchedule $route_schedule)
    {
        $data = $request->validate(ValidationRules::for('route_schedule', 'update', $route_schedule));

        $route_schedule->update($data);

        return response()->json($route_schedule);
    }

    public function destroy(RouteSchedule $route_schedule)
    {
        $route_schedule->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $model = RouteSchedule::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    public function forceDelete(int $id)
    {
        RouteSchedule::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function todayCount(Request $request)
    {
        $day = $request->day;
        if (!$day) {
            return response()->json(['count' => 0, 'customer_count' => 0, 'total_demand' => 0, 'items_demand' => []]);
        }

        $schedules = RouteSchedule::whereRaw("FIND_IN_SET(?, day_of_week)", [$day])
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->get();

        $routesData = [];
        $allCustomerIds = [];

        foreach ($schedules as $sch) {
            $custs = DB::table('route_customers')
                ->where('route_id', $sch->route_id)
                ->where('is_active', 1)
                ->whereNull('deleted_at')
                ->pluck('customer_id')
                ->toArray();

            $routeName = DB::table('routes')->where('id', $sch->route_id)->value('name_ar');
            $employee = Employee::find($sch->employee_id);
            $employeeName = $employee?->full_name_ar ?? '';

            $routesData[] = [
                'schedule_id' => $sch->id,
                'route_id' => $sch->route_id,
                'route_name' => $routeName,
                'employee_name' => $employeeName,
                'customer_count' => count($custs),
            ];

            $allCustomerIds = array_merge($allCustomerIds, $custs);
        }

        $allCustomerIds = array_unique($allCustomerIds);
        $totalCustomerCount = count($allCustomerIds);

        $sixMonthsAgo = now()->subMonths(6)->toDateString();

        $unitsPerCarton = 50;

        $itemDemand = DB::table('sales_invoice_items as sii')
            ->join('sales_invoices as si', 'si.id', '=', 'sii.sales_invoice_id')
            ->whereIn('si.customer_id', $allCustomerIds)
            ->where('si.company_id', $request->user()->company_id)
            ->whereNull('si.deleted_at')
            ->where('si.invoice_date', '>=', $sixMonthsAgo)
            ->select('si.customer_id', 'sii.item_id', DB::raw('SUM(sii.qty) as total_qty'), DB::raw('COUNT(DISTINCT si.id) as invoice_count'))
            ->groupBy('si.customer_id', 'sii.item_id')
            ->get();

        $itemTotals = [];
        $customerTotals = [];

        foreach ($itemDemand as $row) {
            $avg = $row->invoice_count > 0 ? round($row->total_qty / $row->invoice_count, 2) : 0;

            if (!isset($itemTotals[$row->item_id])) {
                $itemTotals[$row->item_id] = 0;
            }
            $itemTotals[$row->item_id] += $avg;

            if (!isset($customerTotals[$row->customer_id])) {
                $customerTotals[$row->customer_id] = 0;
            }
            $customerTotals[$row->customer_id] += $avg;
        }

        $totalAllDemand = array_sum($customerTotals);

        $itemsData = [];
        foreach ($itemTotals as $itemId => $totalAvg) {
            $item = DB::table('items')->where('id', $itemId)->first();
            $itemsData[] = [
                'item_id' => $itemId,
                'item_name' => $item ? ($item->name_ar ?: $item->name_en) : 'â€”',
                'avg_qty' => round($totalAvg, 2),
                'avg_cartons' => round($totalAvg / $unitsPerCarton, 2),
            ];
        }

        $totalDemandCartons = round($totalAllDemand / $unitsPerCarton, 2);

        return response()->json([
            'count' => $schedules->count(),
            'customer_count' => $totalCustomerCount,
            'total_demand' => $totalDemandCartons,
            'total_demand_units' => $totalAllDemand,
            'units_per_carton' => $unitsPerCarton,
            'items_demand' => $itemsData,
            'routes' => $routesData,
        ]);
    }

    public function schema()
    {
        return ValidationRules::for('route_schedule', 'store');
    }
}
