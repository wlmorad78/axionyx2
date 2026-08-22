<?php
/**
 * =====================================================================
 * متحكم (Controller): RouteTemplateController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Route Template
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Route Template" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{RouteTemplate};
use App\Support\ValidationRules;

class RouteTemplateController extends Controller
{
    /**
     * عرض قائمة سجلات (Route Template) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = RouteTemplate::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('route_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Route Template) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_template', 'create'));
        $routeTemplate = RouteTemplate::create($data);
        return response()->json($routeTemplate, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Route Template) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return RouteTemplate::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Route Template) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $routeTemplate = RouteTemplate::findOrFail($id);
        $data = $request->validate(ValidationRules::for('route_template', 'update', $routeTemplate));
        $routeTemplate->update($data);
        return $routeTemplate;
    }

    /**
     * حذف سجل من (Route Template) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $routeTemplate = RouteTemplate::findOrFail($id);
        $routeTemplate->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Route Template) وإعادته للعمل.
     */
    public function restore($id)
    {
        $routeTemplate = RouteTemplate::withTrashed()->findOrFail($id);
        $routeTemplate->restore();
        return $routeTemplate;
    }

    /**
     * حذف نهائي للسجل من (Route Template) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $routeTemplate = RouteTemplate::withTrashed()->findOrFail($id);
        $routeTemplate->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
