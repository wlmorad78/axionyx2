<?php
/**
 * =====================================================================
 * متحكم (Controller): DailyRouteController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Daily Route
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Daily Route" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\DailyRoute;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DailyRouteController extends Controller
{
    /**
     * عرض قائمة سجلات (Daily Route) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['route', 'employee'];
        $query = DailyRoute::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->route_date) {
            $query->where('route_date', $request->route_date);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Daily Route) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('daily_route', 'store'));

        return response()->json(DailyRoute::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Daily Route) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(DailyRoute $daily_route)
    {
        return $daily_route->load([
            'route',
            'employee',
            'customers.customer',
            'events',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Daily Route) بناءً على المعرّف.
     */
    public function update(Request $request, DailyRoute $daily_route)
    {
        $data = $request->validate(ValidationRules::for('daily_route', 'update', $daily_route));

        $daily_route->update($data);

        return response()->json($daily_route);
    }

    /**
     * حذف سجل من (Daily Route) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(DailyRoute $daily_route)
    {
        $daily_route->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Daily Route) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = DailyRoute::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Daily Route) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        DailyRoute::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Daily Route).
     */
    public function schema()
    {
        return ValidationRules::for('daily_route', 'store');
    }
}
