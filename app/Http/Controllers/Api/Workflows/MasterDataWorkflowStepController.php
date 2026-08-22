<?php
/**
 * =====================================================================
 * متحكم (Controller): MasterDataWorkflowStepController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Master Data Workflow Step
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Master Data Workflow Step" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataWorkflowStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataWorkflowStepController extends Controller
{
    /**
     * عرض قائمة سجلات (Master Data Workflow Step) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MasterDataWorkflowStep::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('step_no', 'like', "%{$s}%");
            });
        }

        if ($request->filled('master_data_workflow_id')) $query->where('master_data_workflow_id', $request->master_data_workflow_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Master Data Workflow Step) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_workflow_step', 'create'));
        $masterDataWorkflowStep = MasterDataWorkflowStep::create($data);
        return response()->json($masterDataWorkflowStep, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Master Data Workflow Step) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MasterDataWorkflowStep::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Master Data Workflow Step) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $masterDataWorkflowStep = MasterDataWorkflowStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_workflow_step', 'update', $masterDataWorkflowStep));
        $masterDataWorkflowStep->update($data);
        return $masterDataWorkflowStep;
    }

    /**
     * حذف سجل من (Master Data Workflow Step) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $masterDataWorkflowStep = MasterDataWorkflowStep::findOrFail($id);
        $masterDataWorkflowStep->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Master Data Workflow Step) وإعادته للعمل.
     */
    public function restore($id)
    {
        $masterDataWorkflowStep = MasterDataWorkflowStep::withTrashed()->findOrFail($id);
        $masterDataWorkflowStep->restore();
        return $masterDataWorkflowStep;
    }

    /**
     * حذف نهائي للسجل من (Master Data Workflow Step) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $masterDataWorkflowStep = MasterDataWorkflowStep::withTrashed()->findOrFail($id);
        $masterDataWorkflowStep->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
