<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowTypeController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Type" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowType::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('workflow_code', 'like', "%{$s}%")
                    ->orWhere('workflow_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_type', 'create'));
        $workflowType = WorkflowType::create($data);
        return response()->json($workflowType, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowType::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Type) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowType = WorkflowType::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_type', 'update', $workflowType));
        $workflowType->update($data);
        return $workflowType;
    }

    /**
     * حذف سجل من (Workflow Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowType = WorkflowType::findOrFail($id);
        $workflowType->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Workflow Type) وإعادته للعمل.
     */
    public function restore($id)
    {
        $workflowType = WorkflowType::withTrashed()->findOrFail($id);
        $workflowType->restore();
        return $workflowType;
    }

    /**
     * حذف نهائي للسجل من (Workflow Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $workflowType = WorkflowType::withTrashed()->findOrFail($id);
        $workflowType->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
