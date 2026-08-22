<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowConditionController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Condition
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Condition" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowCondition;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowConditionController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Condition) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowCondition::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('field_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Condition) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_condition', 'create'));
        $workflowCondition = WorkflowCondition::create($data);
        return response()->json($workflowCondition, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Condition) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowCondition::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Condition) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowCondition = WorkflowCondition::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_condition', 'update', $workflowCondition));
        $workflowCondition->update($data);
        return $workflowCondition;
    }

    /**
     * حذف سجل من (Workflow Condition) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowCondition = WorkflowCondition::findOrFail($id);
        $workflowCondition->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
