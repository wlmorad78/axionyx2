<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowDefinitionController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Definition
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Definition" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowDefinition;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowDefinitionController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Definition) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowDefinition::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('workflow_code', 'like', "%{$s}%")
                    ->orWhere('workflow_name', 'like', "%{$s}%")
                    ->orWhere('module_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Definition) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_definition', 'create'));
        $workflowDefinition = WorkflowDefinition::create($data);
        return response()->json($workflowDefinition, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Definition) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowDefinition::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Definition) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowDefinition = WorkflowDefinition::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_definition', 'update', $workflowDefinition));
        $workflowDefinition->update($data);
        return $workflowDefinition;
    }

    /**
     * حذف سجل من (Workflow Definition) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowDefinition = WorkflowDefinition::findOrFail($id);
        $workflowDefinition->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Workflow Definition) وإعادته للعمل.
     */
    public function restore($id)
    {
        $workflowDefinition = WorkflowDefinition::withTrashed()->findOrFail($id);
        $workflowDefinition->restore();
        return $workflowDefinition;
    }

    /**
     * حذف نهائي للسجل من (Workflow Definition) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $workflowDefinition = WorkflowDefinition::withTrashed()->findOrFail($id);
        $workflowDefinition->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
