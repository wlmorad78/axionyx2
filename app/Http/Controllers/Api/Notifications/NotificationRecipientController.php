<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationRecipientController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Recipient
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Recipient" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecipient;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationRecipientController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Recipient) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationRecipient::with(['user']);

        if ($request->filled('notification_id')) $query->where('notification_id', $request->notification_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Recipient) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_recipient', 'create'));
        return response()->json(NotificationRecipient::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Recipient) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationRecipient::with(['user'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Recipient) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationRecipient::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_recipient', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Recipient) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationRecipient::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Recipient) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationRecipient::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Recipient) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationRecipient::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
