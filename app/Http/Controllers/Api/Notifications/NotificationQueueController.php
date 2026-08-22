<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationQueueController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Queue
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Queue" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationQueue;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationQueueController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Queue) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationQueue::with(['notification', 'channel']);

        if ($request->filled('notification_id')) $query->where('notification_id', $request->notification_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Queue) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_queue', 'create'));
        $notificationQueue = NotificationQueue::create($data);
        return response()->json($notificationQueue, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Queue) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationQueue::with(['notification', 'channel'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Queue) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $notificationQueue = NotificationQueue::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_queue', 'update', $notificationQueue));
        $notificationQueue->update($data);
        return $notificationQueue;
    }

    /**
     * حذف سجل من (Notification Queue) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationQueue::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Queue) وإعادته للعمل.
     */
    public function restore($id)
    {
        $notificationQueue = NotificationQueue::withTrashed()->findOrFail($id);
        $notificationQueue->restore();
        return $notificationQueue;
    }

    /**
     * حذف نهائي للسجل من (Notification Queue) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationQueue::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
