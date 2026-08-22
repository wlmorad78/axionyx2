<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowDelegationController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Delegation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Delegation" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowDelegation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowDelegationController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Delegation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowDelegation::query()->with(['fromUser', 'toUser']);

        if ($request->filled('from_user_id')) $query->where('from_user_id', $request->from_user_id);
        if ($request->filled('to_user_id')) $query->where('to_user_id', $request->to_user_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Delegation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_delegation', 'create'));
        $workflowDelegation = WorkflowDelegation::create($data);
        return response()->json($workflowDelegation, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Delegation) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowDelegation::with(['fromUser', 'toUser'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Delegation) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowDelegation = WorkflowDelegation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_delegation', 'update', $workflowDelegation));
        $workflowDelegation->update($data);
        return $workflowDelegation;
    }

    /**
     * حذف سجل من (Workflow Delegation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowDelegation = WorkflowDelegation::findOrFail($id);
        $workflowDelegation->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
