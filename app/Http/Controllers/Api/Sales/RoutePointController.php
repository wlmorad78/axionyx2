<?php
/**
 * =====================================================================
 * متحكم (Controller): RoutePointController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Route Point
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Route Point" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\RoutePoint;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RoutePointController extends Controller
{
    /**
     * عرض قائمة سجلات (Route Point) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['route'];
        $query = RoutePoint::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderBy('sequence_no')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Route Point) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_point', 'store'));

        return response()->json(RoutePoint::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Route Point) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(RoutePoint $route_point)
    {
        return $route_point->load(['route']);
    }

    /**
     * تحديث بيانات سجل موجود من (Route Point) بناءً على المعرّف.
     */
    public function update(Request $request, RoutePoint $route_point)
    {
        $data = $request->validate(ValidationRules::for('route_point', 'update', $route_point));

        $route_point->update($data);

        return response()->json($route_point);
    }

    /**
     * حذف سجل من (Route Point) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(RoutePoint $route_point)
    {
        $route_point->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Route Point) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = RoutePoint::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Route Point) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        RoutePoint::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Route Point).
     */
    public function schema()
    {
        return ValidationRules::for('route_point', 'store');
    }
}
