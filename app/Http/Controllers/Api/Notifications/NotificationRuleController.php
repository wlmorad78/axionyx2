<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationRuleController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Rule" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationRule;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationRule::with(['event', 'template']);

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('notification_event_id')) $query->where('notification_event_id', $request->notification_event_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_rule', 'create'));
        return response()->json(NotificationRule::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationRule::with(['event', 'template'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Rule) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_rule', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationRule::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Rule) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationRule::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Rule) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationRule::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
