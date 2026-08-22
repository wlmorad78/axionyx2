<?php
/**
 * =====================================================================
 * متحكم (Controller): MessageTemplateController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Message Template
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Message Template" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{MessageTemplate};
use App\Support\ValidationRules;

class MessageTemplateController extends Controller
{
    /**
     * عرض قائمة سجلات (Message Template) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MessageTemplate::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('template_code', 'like', "%{$s}%")
                  ->orWhere('template_name', 'like', "%{$s}%")
                  ->orWhere('channel', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Message Template) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('message_template', 'create'));
        $messageTemplate = MessageTemplate::create($data);
        return response()->json($messageTemplate, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Message Template) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MessageTemplate::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Message Template) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $messageTemplate = MessageTemplate::findOrFail($id);
        $data = $request->validate(ValidationRules::for('message_template', 'update', $messageTemplate));
        $messageTemplate->update($data);
        return $messageTemplate;
    }

    /**
     * حذف سجل من (Message Template) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $messageTemplate = MessageTemplate::findOrFail($id);
        $messageTemplate->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Message Template) وإعادته للعمل.
     */
    public function restore($id)
    {
        $messageTemplate = MessageTemplate::withTrashed()->findOrFail($id);
        $messageTemplate->restore();
        return $messageTemplate;
    }

    /**
     * حذف نهائي للسجل من (Message Template) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $messageTemplate = MessageTemplate::withTrashed()->findOrFail($id);
        $messageTemplate->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
