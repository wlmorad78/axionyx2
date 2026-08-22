<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowActionLogController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Action Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Action Log" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowActionLog;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowActionLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Action Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowActionLog::query()->with(['workflowInstance', 'actionBy']);

        if ($request->filled('workflow_instance_id')) $query->where('workflow_instance_id', $request->workflow_instance_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Action Log) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_action_log', 'create'));
        $workflowActionLog = WorkflowActionLog::create($data);
        return response()->json($workflowActionLog, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Action Log) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowActionLog::with(['workflowInstance', 'actionBy'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Action Log) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowActionLog = WorkflowActionLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_action_log', 'update', $workflowActionLog));
        $workflowActionLog->update($data);
        return $workflowActionLog;
    }

    /**
     * حذف سجل من (Workflow Action Log) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowActionLog = WorkflowActionLog::findOrFail($id);
        $workflowActionLog->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
