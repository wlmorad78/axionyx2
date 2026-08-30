<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationTemplateController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Template
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Template" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationTemplateController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Template) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationTemplate::with(['notificationType', 'channel']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('template_code', 'like', "%{$s}%")
                    ->orWhere('template_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('notification_type_id')) $query->where('notification_type_id', $request->notification_type_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Template) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_template', 'create'));
        $notificationTemplate = NotificationTemplate::create($data);
        return response()->json($notificationTemplate, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Template) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationTemplate::with(['notificationType', 'channel'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Template) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $notificationTemplate = NotificationTemplate::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_template', 'update', $notificationTemplate));
        $notificationTemplate->update($data);
        return $notificationTemplate;
    }

    /**
     * حذف سجل من (Notification Template) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationTemplate::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Template) وإعادته للعمل.
     */
    public function restore($id)
    {
        $notificationTemplate = NotificationTemplate::withTrashed()->findOrFail($id);
        $notificationTemplate->restore();
        return $notificationTemplate;
    }

    /**
     * حذف نهائي للسجل من (Notification Template) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationTemplate::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
