<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowSlaRuleController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Sla Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Sla Rule" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowSlaRule;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowSlaRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Sla Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowSlaRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Sla Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_sla_rule', 'create'));
        $workflowSlaRule = WorkflowSlaRule::create($data);
        return response()->json($workflowSlaRule, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Sla Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowSlaRule::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Sla Rule) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowSlaRule = WorkflowSlaRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_sla_rule', 'update', $workflowSlaRule));
        $workflowSlaRule->update($data);
        return $workflowSlaRule;
    }

    /**
     * حذف سجل من (Workflow Sla Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowSlaRule = WorkflowSlaRule::findOrFail($id);
        $workflowSlaRule->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
