<?php
/**
 * =====================================================================
 * متحكم (Controller): ScheduledNotificationController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Scheduled Notification
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Scheduled Notification" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\ScheduledNotification;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ScheduledNotificationController extends Controller
{
    /**
     * عرض قائمة سجلات (Scheduled Notification) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ScheduledNotification::with(['template']);

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Scheduled Notification) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('scheduled_notification', 'create'));
        return response()->json(ScheduledNotification::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Scheduled Notification) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return ScheduledNotification::with(['template'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Scheduled Notification) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = ScheduledNotification::findOrFail($id);
        $data = $request->validate(ValidationRules::for('scheduled_notification', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Scheduled Notification) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        ScheduledNotification::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Scheduled Notification) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = ScheduledNotification::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Scheduled Notification) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        ScheduledNotification::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
