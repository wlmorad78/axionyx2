<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleCostAnalysisController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Cost Analysis
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Cost Analysis" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleCostAnalysis;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleCostAnalysisController extends Controller
{
    /**
     * تقرير تكلفة المركبة - يحسب التكاليف ديناميكياً من كل الجداول المرتبطة.
     */
    public function costReport(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->endOfMonth()->toDateString());

        $vehicleQuery = DB::table('vehicles')
            ->whereNull('deleted_at');

        if ($request->filled('vehicle_id')) {
            $vehicleQuery->where('id', $request->vehicle_id);
        }

        $vehicles = $vehicleQuery->get();
        $results = [];

        foreach ($vehicles as $vehicle) {
            $vid = $vehicle->id;

            // 1. تكلفة الوقود
            $fuelCost = (float) DB::table('vehicle_fuel_transactions')
                ->where('vehicle_id', $vid)
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->whereNull('deleted_at')
                ->sum('fuel_cost');

            $totalFuelQty = (float) DB::table('vehicle_fuel_transactions')
                ->where('vehicle_id', $vid)
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->whereNull('deleted_at')
                ->sum('fuel_qty');

            // 2. تكلفة الصيانة
            $maintenanceCost = (float) DB::table('vehicle_maintenance')
                ->where('vehicle_id', $vid)
                ->whereBetween('maintenance_date', [$dateFrom, $dateTo])
                ->whereNull('deleted_at')
                ->sum('cost');

            // 3. تكلفة التأمين (تُحسب بالتقسيم على طول الفترة)
            $insuranceCost = (float) DB::table('vehicle_insurance')
                ->where('vehicle_id', $vid)
                ->where('start_date', '<=', $dateTo)
                ->where('end_date', '>=', $dateFrom)
                ->whereNull('deleted_at')
                ->sum('premium_amount');

            // 4. المصروفات اليومية (موقف + رسوم + أخرى)
            $dailyExpenses = DB::table('vehicle_daily_expenses')
                ->where('vehicle_id', $vid)
                ->whereBetween('expense_date', [$dateFrom, $dateTo])
                ->whereNull('deleted_at')
                ->select('expense_type', DB::raw('SUM(amount) as total'))
                ->groupBy('expense_type')
                ->get()
                ->pluck('total', 'expense_type');

            $tollCost      = (float) ($dailyExpenses['TOLL'] ?? 0);
            $parkingCost   = (float) ($dailyExpenses['PARKING'] ?? 0);
            $maintenanceDaily = (float) ($dailyExpenses['MAINTENANCE'] ?? 0);
            $otherCost     = (float) ($dailyExpenses['OTHER'] ?? 0);
            $fuelDaily     = (float) ($dailyExpenses['FUEL'] ?? 0);

            // 5. إجمالي كيلومترات (من عداد المسافة في معاملات الوقود)
            $totalKm = (float) DB::table('vehicle_fuel_transactions')
                ->where('vehicle_id', $vid)
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->whereNotNull('odometer')
                ->whereNull('deleted_at')
                ->max('odometer');

            // حساب المسافة المقطوعة من الفرق بين أصغر وأكبر عداد
            $minOdometer = (float) DB::table('vehicle_fuel_transactions')
                ->where('vehicle_id', $vid)
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->whereNotNull('odometer')
                ->whereNull('deleted_at')
                ->min('odometer');

            $drivenKm = max(0, $totalKm - $minOdometer);

            // إجمالي التكاليف
            $totalFuel       = $fuelCost + $fuelDaily;
            $totalMaintenance = $maintenanceCost + $maintenanceDaily;
            $totalOther      = $tollCost + $parkingCost + $otherCost;
            $totalCost       = $totalFuel + $totalMaintenance + $insuranceCost + $tollCost + $parkingCost + $otherCost;
            $costPerKm       = $drivenKm > 0 ? round($totalCost / $drivenKm, 4) : 0;

            $results[] = [
                'vehicle_id'        => $vid,
                'plate_number'      => $vehicle->plate_number ?? null,
                'vehicle_code'      => $vehicle->vehicle_code ?? null,
                'date_from'         => $dateFrom,
                'date_to'           => $dateTo,
                'fuel_cost'         => round($totalFuel, 2),
                'fuel_qty'          => round($totalFuelQty + ($dailyExpenses['FUEL'] ? 0 : 0), 2),
                'maintenance_cost'  => round($totalMaintenance, 2),
                'insurance_cost'    => round($insuranceCost, 2),
                'toll_cost'         => round($tollCost, 2),
                'parking_cost'      => round($parkingCost, 2),
                'other_cost'        => round($otherCost, 2),
                'total_cost'        => round($totalCost, 2),
                'total_km'          => round($drivenKm, 2),
                'cost_per_km'       => $costPerKm,
            ];
        }

        return response()->json([
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'data'      => $results,
        ]);
    }

    /**
     * عرض قائمة سجلات (Vehicle Cost Analysis) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleCostAnalysis::with(['vehicle']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('analysis_period', 'like', "%{$s}%");
            });
        }
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Cost Analysis) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_cost_analysis', 'create'));
        $item = VehicleCostAnalysis::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleCostAnalysis::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleCostAnalysis::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_cost_analysis', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Cost Analysis) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleCostAnalysis::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Cost Analysis) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleCostAnalysis::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Cost Analysis) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleCostAnalysis::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
