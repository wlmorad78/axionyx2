<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowEscalationController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Escalation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Escalation" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowEscalation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowEscalationController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Escalation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowEscalation::query()->with(['workflowStep', 'escalateToRole']);

        if ($request->filled('workflow_step_id')) $query->where('workflow_step_id', $request->workflow_step_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Escalation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_escalation', 'create'));
        $workflowEscalation = WorkflowEscalation::create($data);
        return response()->json($workflowEscalation, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Escalation) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowEscalation::with(['workflowStep', 'escalateToRole'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Escalation) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowEscalation = WorkflowEscalation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_escalation', 'update', $workflowEscalation));
        $workflowEscalation->update($data);
        return $workflowEscalation;
    }

    /**
     * حذف سجل من (Workflow Escalation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowEscalation = WorkflowEscalation::findOrFail($id);
        $workflowEscalation->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
