<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowNotificationController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Notification
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Notification" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowNotification;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowNotificationController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Notification) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowNotification::query()->with(['workflowInstance', 'user']);

        if ($request->filled('workflow_instance_id')) $query->where('workflow_instance_id', $request->workflow_instance_id);
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Notification) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_notification', 'create'));
        $workflowNotification = WorkflowNotification::create($data);
        return response()->json($workflowNotification, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Notification) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowNotification::with(['workflowInstance', 'user'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Notification) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowNotification = WorkflowNotification::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_notification', 'update', $workflowNotification));
        $workflowNotification->update($data);
        return $workflowNotification;
    }

    /**
     * حذف سجل من (Workflow Notification) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowNotification = WorkflowNotification::findOrFail($id);
        $workflowNotification->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
