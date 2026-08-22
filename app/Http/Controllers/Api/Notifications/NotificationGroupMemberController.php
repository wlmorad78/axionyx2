<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationGroupMemberController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Group Member
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Group Member" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationGroupMember;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationGroupMemberController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Group Member) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationGroupMember::with(['user']);

        if ($request->filled('notification_group_id')) $query->where('notification_group_id', $request->notification_group_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Group Member) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_group_member', 'create'));
        return response()->json(NotificationGroupMember::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Group Member) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationGroupMember::with(['user'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Group Member) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationGroupMember::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_group_member', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Group Member) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationGroupMember::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Group Member) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationGroupMember::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Group Member) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationGroupMember::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
