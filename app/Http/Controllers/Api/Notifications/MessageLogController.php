<?php
/**
 * =====================================================================
 * متحكم (Controller): MessageLogController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Message Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Message Log" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{MessageLog};
use App\Support\ValidationRules;

class MessageLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Message Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MessageLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('channel', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Message Log) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('message_log', 'create'));
        $messageLog = MessageLog::create($data);
        return response()->json($messageLog, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Message Log) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MessageLog::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Message Log) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $messageLog = MessageLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('message_log', 'update', $messageLog));
        $messageLog->update($data);
        return $messageLog;
    }

    /**
     * حذف سجل من (Message Log) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $messageLog = MessageLog::findOrFail($id);
        $messageLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Message Log) وإعادته للعمل.
     */
    public function restore($id)
    {
        $messageLog = MessageLog::withTrashed()->findOrFail($id);
        $messageLog->restore();
        return $messageLog;
    }

    /**
     * حذف نهائي للسجل من (Message Log) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $messageLog = MessageLog::withTrashed()->findOrFail($id);
        $messageLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
