<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationGroupController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Group
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Group" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationGroup;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationGroupController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Group) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationGroup::with(['members']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('group_code', 'like', "%{$s}%")
                    ->orWhere('group_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Group) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_group', 'create'));
        return response()->json(NotificationGroup::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Group) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationGroup::with(['members'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Group) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationGroup::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_group', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Group) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationGroup::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Group) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationGroup::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Group) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationGroup::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
