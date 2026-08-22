<?php
/**
 * =====================================================================
 * متحكم (Controller): ForecastHistoryController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Forecast History
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Forecast History" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ForecastHistory};
use App\Support\ValidationRules;

class ForecastHistoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Forecast History) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ForecastHistory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('actual_qty', 'like', "%{$s}%")
                  ->orWhere('forecast_qty', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Forecast History) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('forecast_history', 'create'));
        $forecastHistory = ForecastHistory::create($data);
        return response()->json($forecastHistory, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Forecast History) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return ForecastHistory::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Forecast History) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $forecastHistory = ForecastHistory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('forecast_history', 'update', $forecastHistory));
        $forecastHistory->update($data);
        return $forecastHistory;
    }

    /**
     * حذف سجل من (Forecast History) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $forecastHistory = ForecastHistory::findOrFail($id);
        $forecastHistory->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Forecast History) وإعادته للعمل.
     */
    public function restore($id)
    {
        $forecastHistory = ForecastHistory::withTrashed()->findOrFail($id);
        $forecastHistory->restore();
        return $forecastHistory;
    }

    /**
     * حذف نهائي للسجل من (Forecast History) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $forecastHistory = ForecastHistory::withTrashed()->findOrFail($id);
        $forecastHistory->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
