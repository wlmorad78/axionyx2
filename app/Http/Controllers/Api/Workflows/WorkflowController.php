<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Workflow::query()->with(['workflowType']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('workflow_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('workflow_type_id')) $query->where('workflow_type_id', $request->workflow_type_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow', 'create'));
        $workflow = Workflow::create($data);
        return response()->json($workflow, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Workflow::with(['workflowType'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflow = Workflow::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow', 'update', $workflow));
        $workflow->update($data);
        return $workflow;
    }

    /**
     * حذف سجل من (Workflow) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflow = Workflow::findOrFail($id);
        $workflow->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Workflow) وإعادته للعمل.
     */
    public function restore($id)
    {
        $workflow = Workflow::withTrashed()->findOrFail($id);
        $workflow->restore();
        return $workflow;
    }

    /**
     * حذف نهائي للسجل من (Workflow) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $workflow = Workflow::withTrashed()->findOrFail($id);
        $workflow->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
