<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowInstanceController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Instance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Instance" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowInstance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowInstanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Instance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowInstance::query()->with(['workflow', 'startedBy']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('instance_no', 'like', "%{$s}%")
                    ->orWhere('entity_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('workflow_id')) $query->where('workflow_id', $request->workflow_id);
        if ($request->filled('entity_type')) $query->where('entity_type', $request->entity_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Instance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_instance', 'create'));

        if (empty($data['instance_no'])) {
            $lastInstance = WorkflowInstance::withTrashed()->orderByDesc('id')->first();
            $nextNumber = $lastInstance ? (int) substr($lastInstance->instance_no, 3) + 1 : 1;
            $data['instance_no'] = 'WI-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        $workflowInstance = WorkflowInstance::create($data);
        return response()->json($workflowInstance, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Instance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowInstance::with(['workflow', 'startedBy'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Instance) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowInstance = WorkflowInstance::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_instance', 'update', $workflowInstance));
        $workflowInstance->update($data);
        return $workflowInstance;
    }

    /**
     * حذف سجل من (Workflow Instance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowInstance = WorkflowInstance::findOrFail($id);
        $workflowInstance->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Workflow Instance) وإعادته للعمل.
     */
    public function restore($id)
    {
        $workflowInstance = WorkflowInstance::withTrashed()->findOrFail($id);
        $workflowInstance->restore();
        return $workflowInstance;
    }

    /**
     * حذف نهائي للسجل من (Workflow Instance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $workflowInstance = WorkflowInstance::withTrashed()->findOrFail($id);
        $workflowInstance->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
