<?php
/**
 * =====================================================================
 * متحكم (Controller): RouteAssignmentController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Route Assignment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Route Assignment" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\RouteAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RouteAssignmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Route Assignment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['route', 'vehicle', 'driver', 'assistant'];
        $query = RouteAssignment::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->driver_id) {
            $query->where('driver_id', $request->driver_id);
        }
        if ($request->assignment_date) {
            $query->where('assignment_date', $request->assignment_date);
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
     * إنشاء سجل جديد لـ (Route Assignment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_assignment', 'store'));

        return response()->json(RouteAssignment::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Route Assignment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(RouteAssignment $route_assignment)
    {
        return $route_assignment->load(['route', 'vehicle', 'driver', 'assistant']);
    }

    /**
     * تحديث بيانات سجل موجود من (Route Assignment) بناءً على المعرّف.
     */
    public function update(Request $request, RouteAssignment $route_assignment)
    {
        $data = $request->validate(ValidationRules::for('route_assignment', 'update', $route_assignment));

        $route_assignment->update($data);

        return response()->json($route_assignment);
    }

    /**
     * حذف سجل من (Route Assignment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(RouteAssignment $route_assignment)
    {
        $route_assignment->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Route Assignment) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = RouteAssignment::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Route Assignment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        RouteAssignment::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Route Assignment).
     */
    public function schema()
    {
        return ValidationRules::for('route_assignment', 'store');
    }
}
