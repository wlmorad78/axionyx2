<?php
/**
 * =====================================================================
 * متحكم (Controller): RouteEventController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Route Event
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Route Event" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\RouteEvent;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RouteEventController extends Controller
{
    /**
     * عرض قائمة سجلات (Route Event) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['dailyRoute', 'customer'];
        $query = RouteEvent::with($with);

        if ($request->daily_route_id) {
            $query->where('daily_route_id', $request->daily_route_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->event_type) {
            $query->where('event_type', $request->event_type);
        }
        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Route Event) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_event', 'store'));

        return response()->json(RouteEvent::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Route Event) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(RouteEvent $route_event)
    {
        return $route_event->load(['dailyRoute', 'customer']);
    }

    /**
     * تحديث بيانات سجل موجود من (Route Event) بناءً على المعرّف.
     */
    public function update(Request $request, RouteEvent $route_event)
    {
        $data = $request->validate(ValidationRules::for('route_event', 'update', $route_event));

        $route_event->update($data);

        return response()->json($route_event);
    }

    /**
     * حذف سجل من (Route Event) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(RouteEvent $route_event)
    {
        $route_event->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Route Event) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = RouteEvent::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Route Event) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        RouteEvent::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Route Event).
     */
    public function schema()
    {
        return ValidationRules::for('route_event', 'store');
    }
}
