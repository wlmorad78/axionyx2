<?php
/**
 * =====================================================================
 * متحكم (Controller): MasterDataWorkflowController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Master Data Workflow
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Master Data Workflow" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataWorkflow;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataWorkflowController extends Controller
{
    /**
     * عرض قائمة سجلات (Master Data Workflow) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MasterDataWorkflow::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('workflow_name', 'like', "%{$s}%")
                  ->orWhere('entity_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Master Data Workflow) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_workflow', 'create'));
        $masterDataWorkflow = MasterDataWorkflow::create($data);
        return response()->json($masterDataWorkflow, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Master Data Workflow) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MasterDataWorkflow::with('steps')->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Master Data Workflow) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $masterDataWorkflow = MasterDataWorkflow::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_workflow', 'update', $masterDataWorkflow));
        $masterDataWorkflow->update($data);
        return $masterDataWorkflow;
    }

    /**
     * حذف سجل من (Master Data Workflow) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $masterDataWorkflow = MasterDataWorkflow::findOrFail($id);
        $masterDataWorkflow->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Master Data Workflow) وإعادته للعمل.
     */
    public function restore($id)
    {
        $masterDataWorkflow = MasterDataWorkflow::withTrashed()->findOrFail($id);
        $masterDataWorkflow->restore();
        return $masterDataWorkflow;
    }

    /**
     * حذف نهائي للسجل من (Master Data Workflow) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $masterDataWorkflow = MasterDataWorkflow::withTrashed()->findOrFail($id);
        $masterDataWorkflow->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
