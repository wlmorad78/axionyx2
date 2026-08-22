<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationPreferenceController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Preference
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Preference" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Preference) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationPreference::with(['notificationType', 'channel']);

        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('notification_type_id')) $query->where('notification_type_id', $request->notification_type_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Preference) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_preference', 'create'));
        return response()->json(NotificationPreference::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Preference) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationPreference::with(['notificationType', 'channel'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Preference) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationPreference::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_preference', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Preference) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationPreference::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Preference) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationPreference::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Preference) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationPreference::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
