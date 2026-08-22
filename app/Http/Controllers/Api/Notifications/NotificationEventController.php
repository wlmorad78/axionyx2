<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationEventController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Event
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Event" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationEvent;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationEventController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Event) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationEvent::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('event_code', 'like', "%{$s}%")
                    ->orWhere('event_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('entity_type')) $query->where('entity_type', $request->entity_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Event) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_event', 'create'));
        return response()->json(NotificationEvent::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Event) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationEvent::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Event) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationEvent::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_event', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Event) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationEvent::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Event) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationEvent::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Event) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationEvent::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
