<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationTypeController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Type" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationType::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('type_code', 'like', "%{$s}%")
                    ->orWhere('type_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_type', 'create'));
        return response()->json(NotificationType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationType::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Type) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationType::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_type', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationType::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Type) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationType::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationType::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
