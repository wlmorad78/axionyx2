<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowInstanceStepController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Instance Step
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Instance Step" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowInstanceStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowInstanceStepController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Instance Step) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowInstanceStep::query()->with(['workflowStep', 'assignedTo']);

        if ($request->filled('workflow_instance_id')) $query->where('workflow_instance_id', $request->workflow_instance_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Instance Step) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_instance_step', 'create'));
        $workflowInstanceStep = WorkflowInstanceStep::create($data);
        return response()->json($workflowInstanceStep, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Instance Step) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowInstanceStep::with(['workflowStep', 'assignedTo'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Instance Step) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowInstanceStep = WorkflowInstanceStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_instance_step', 'update', $workflowInstanceStep));
        $workflowInstanceStep->update($data);
        return $workflowInstanceStep;
    }

    /**
     * حذف سجل من (Workflow Instance Step) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowInstanceStep = WorkflowInstanceStep::findOrFail($id);
        $workflowInstanceStep->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
