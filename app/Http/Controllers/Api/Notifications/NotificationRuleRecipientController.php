<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationRuleRecipientController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Rule Recipient
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Rule Recipient" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationRuleRecipient;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationRuleRecipientController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Rule Recipient) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationRuleRecipient::with(['notificationRule']);

        if ($request->filled('notification_rule_id')) $query->where('notification_rule_id', $request->notification_rule_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Rule Recipient) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_rule_recipient', 'create'));
        return response()->json(NotificationRuleRecipient::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Rule Recipient) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationRuleRecipient::with(['notificationRule'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Rule Recipient) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationRuleRecipient::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_rule_recipient', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Rule Recipient) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationRuleRecipient::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Rule Recipient) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationRuleRecipient::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Rule Recipient) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationRuleRecipient::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
