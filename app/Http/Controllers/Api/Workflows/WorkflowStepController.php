<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowStepController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Step
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Step" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowStepController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Step) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowStep::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('step_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);
        if ($request->filled('workflow_definition_id')) $query->where('workflow_definition_id', $request->workflow_definition_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Step) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_step', 'create'));
        $workflowStep = WorkflowStep::create($data);
        return response()->json($workflowStep, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Step) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowStep::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Step) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowStep = WorkflowStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_step', 'update', $workflowStep));
        $workflowStep->update($data);
        return $workflowStep;
    }

    /**
     * حذف سجل من (Workflow Step) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowStep = WorkflowStep::findOrFail($id);
        $workflowStep->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Workflow Step) وإعادته للعمل.
     */
    public function restore($id)
    {
        $workflowStep = WorkflowStep::withTrashed()->findOrFail($id);
        $workflowStep->restore();
        return $workflowStep;
    }

    /**
     * حذف نهائي للسجل من (Workflow Step) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $workflowStep = WorkflowStep::withTrashed()->findOrFail($id);
        $workflowStep->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
