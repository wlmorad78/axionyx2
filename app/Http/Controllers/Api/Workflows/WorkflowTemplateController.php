<?php
/**
 * =====================================================================
 * متحكم (Controller): WorkflowTemplateController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Workflow Template
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Workflow Template" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\WorkflowTemplate;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WorkflowTemplateController extends Controller
{
    /**
     * عرض قائمة سجلات (Workflow Template) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WorkflowTemplate::query()->with(['templateSteps']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('template_name', 'like', "%{$s}%")
                    ->orWhere('entity_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Workflow Template) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('workflow_template', 'create'));
        $workflowTemplate = WorkflowTemplate::create($data);
        return response()->json($workflowTemplate, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Workflow Template) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return WorkflowTemplate::with(['templateSteps'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Workflow Template) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $workflowTemplate = WorkflowTemplate::findOrFail($id);
        $data = $request->validate(ValidationRules::for('workflow_template', 'update', $workflowTemplate));
        $workflowTemplate->update($data);
        return $workflowTemplate;
    }

    /**
     * حذف سجل من (Workflow Template) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $workflowTemplate = WorkflowTemplate::findOrFail($id);
        $workflowTemplate->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
