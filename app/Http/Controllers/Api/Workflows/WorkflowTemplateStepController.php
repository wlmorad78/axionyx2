<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowTemplateStepController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Template Step
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Template Step" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowTemplateStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowTemplateStepController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Template Step) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowTemplateStep::query()->with(['role']);

        if ($request->filled('workflow_template_id')) $query->where('workflow_template_id', $request->workflow_template_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Template Step) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_template_step', 'create'));
        $workflowTemplateStep = WorkflowTemplateStep::create($data);
        return response()->json($workflowTemplateStep, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Template Step) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowTemplateStep::with(['role'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Template Step) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowTemplateStep = WorkflowTemplateStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_template_step', 'update', $workflowTemplateStep));
        $workflowTemplateStep->update($data);
        return $workflowTemplateStep;
    }

    /**
     * حذف سجل من (Workflow Template Step) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowTemplateStep = WorkflowTemplateStep::findOrFail($id);
        $workflowTemplateStep->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
