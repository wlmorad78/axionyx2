<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowRoleController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Role
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Role" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowRole;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowRoleController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Role) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowRole::query()->with(['role']);

        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Role) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_role', 'create'));
        $workflowRole = WorkflowRole::create($data);
        return response()->json($workflowRole, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Role) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowRole::with(['role'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Role) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowRole = WorkflowRole::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_role', 'update', $workflowRole));
        $workflowRole->update($data);
        return $workflowRole;
    }

    /**
     * حذف سجل من (Workflow Role) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowRole = WorkflowRole::findOrFail($id);
        $workflowRole->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
