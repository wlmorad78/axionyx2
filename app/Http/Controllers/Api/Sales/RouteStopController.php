<?php
/**
 * =====================================================================
 * متحكم (Controller): RouteStopController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Route Stop
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Route Stop" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{RouteStop};
use App\Support\ValidationRules;

class RouteStopController extends Controller
{
    /**
     * عرض قائمة سجلات (Route Stop) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = RouteStop::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('sequence_no', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Route Stop) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_stop', 'create'));
        $routeStop = RouteStop::create($data);
        return response()->json($routeStop, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Route Stop) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return RouteStop::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Route Stop) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $routeStop = RouteStop::findOrFail($id);
        $data = $request->validate(ValidationRules::for('route_stop', 'update', $routeStop));
        $routeStop->update($data);
        return $routeStop;
    }

    /**
     * حذف سجل من (Route Stop) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $routeStop = RouteStop::findOrFail($id);
        $routeStop->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Route Stop) وإعادته للعمل.
     */
    public function restore($id)
    {
        $routeStop = RouteStop::withTrashed()->findOrFail($id);
        $routeStop->restore();
        return $routeStop;
    }

    /**
     * حذف نهائي للسجل من (Route Stop) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $routeStop = RouteStop::withTrashed()->findOrFail($id);
        $routeStop->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
