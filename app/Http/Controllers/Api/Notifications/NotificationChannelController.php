<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationChannelController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification Channel
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification Channel" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationChannelController extends Controller
{
    /**
     * عرض قائمة سجلات (Notification Channel) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = NotificationChannel::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('channel_code', 'like', "%{$s}%")
                    ->orWhere('channel_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Notification Channel) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_channel', 'create'));
        return response()->json(NotificationChannel::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Notification Channel) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return NotificationChannel::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Notification Channel) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = NotificationChannel::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_channel', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Notification Channel) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        NotificationChannel::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Notification Channel) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = NotificationChannel::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Notification Channel) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        NotificationChannel::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
