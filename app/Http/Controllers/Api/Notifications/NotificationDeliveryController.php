<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationDeliveryController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Delivery
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Delivery" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationDeliveryController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Delivery) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationDelivery::with(['channel']);

        if ($request->filled('notification_id')) $query->where('notification_id', $request->notification_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Delivery) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_delivery', 'create'));
        return response()->json(NotificationDelivery::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Delivery) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationDelivery::with(['channel'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Delivery) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationDelivery::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_delivery', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Delivery) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationDelivery::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Delivery) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationDelivery::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Delivery) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationDelivery::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
