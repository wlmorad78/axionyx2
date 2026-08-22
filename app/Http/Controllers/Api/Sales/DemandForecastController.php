<?php
/**
 * =====================================================================
 * متحكم (Controller): DemandForecastController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Demand Forecast
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Demand Forecast" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{DemandForecast};
use App\Support\ValidationRules;

class DemandForecastController extends Controller
{
    /**
     * عرض قائمة سجلات (Demand Forecast) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DemandForecast::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('forecast_qty', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Demand Forecast) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('demand_forecast', 'create'));
        $demandForecast = DemandForecast::create($data);
        return response()->json($demandForecast, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Demand Forecast) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return DemandForecast::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Demand Forecast) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $demandForecast = DemandForecast::findOrFail($id);
        $data = $request->validate(ValidationRules::for('demand_forecast', 'update', $demandForecast));
        $demandForecast->update($data);
        return $demandForecast;
    }

    /**
     * حذف سجل من (Demand Forecast) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $demandForecast = DemandForecast::findOrFail($id);
        $demandForecast->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Demand Forecast) وإعادته للعمل.
     */
    public function restore($id)
    {
        $demandForecast = DemandForecast::withTrashed()->findOrFail($id);
        $demandForecast->restore();
        return $demandForecast;
    }

    /**
     * حذف نهائي للسجل من (Demand Forecast) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $demandForecast = DemandForecast::withTrashed()->findOrFail($id);
        $demandForecast->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
