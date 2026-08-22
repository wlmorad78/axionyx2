<?php
/**
 * =====================================================================
 * متحكم (Controller): RouteScheduleController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Route Schedule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Route Schedule" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\RouteSchedule;
use App\Models\HR\Employee;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteScheduleController extends Controller
{
    /**
     * عرض قائمة سجلات (Route Schedule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
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

    /**
     * إنشاء سجل جديد لـ (Route Schedule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_schedule', 'store'));

        return response()->json(RouteSchedule::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Route Schedule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(RouteSchedule $route_schedule)
    {
        return $route_schedule->load([
            'route',
            'employee',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Route Schedule) بناءً على المعرّف.
     */
    public function update(Request $request, RouteSchedule $route_schedule)
    {
        $data = $request->validate(ValidationRules::for('route_schedule', 'update', $route_schedule));

        $route_schedule->update($data);

        return response()->json($route_schedule);
    }

    /**
     * حذف سجل من (Route Schedule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(RouteSchedule $route_schedule)
    {
        $route_schedule->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Route Schedule) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = RouteSchedule::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Route Schedule) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        RouteSchedule::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * دالة معالجة: todayCount — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Route Schedule).
     */
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

        // Bulk fetch routes, employees, and route_customers
        $routeIds = $schedules->pluck('route_id')->unique()->values();
        $employeeIds = $schedules->pluck('employee_id')->filter()->unique()->values();

        $routesMap = DB::table('routes')->whereIn('id', $routeIds)->get()->keyBy('id');
        $employeesMap = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

        $routeCustomersMap = DB::table('route_customers')
            ->whereIn('route_id', $routeIds)
            ->where('is_active', 1)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('route_id')
            ->map(fn($rows) => $rows->pluck('customer_id')->toArray());

        $routesData = [];
        $allCustomerIds = [];

        foreach ($schedules as $sch) {
            $custs = $routeCustomersMap->get($sch->route_id, []);
            $routeName = $routesMap->get($sch->route_id)?->name_ar ?? '';
            $employeeName = $employeesMap->get($sch->employee_id)?->full_name_ar ?? '';

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
        if (!empty($itemTotals)) {
            $itemsMap = DB::table('items')->whereIn('id', array_keys($itemTotals))->get()->keyBy('id');

            foreach ($itemTotals as $itemId => $totalAvg) {
                $item = $itemsMap->get($itemId);
                $itemsData[] = [
                    'item_id' => $itemId,
                    'item_name' => $item ? ($item->name_ar ?: $item->name_en) : '—',
                    'avg_qty' => round($totalAvg, 2),
                    'avg_cartons' => round($totalAvg / $unitsPerCarton, 2),
                ];
            }
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

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Route Schedule).
     */
    public function schema()
    {
        return ValidationRules::for('route_schedule', 'store');
    }
}
