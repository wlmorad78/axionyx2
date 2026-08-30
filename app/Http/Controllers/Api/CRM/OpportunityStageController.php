<?php
/**
 * =====================================================================
 * متحكم (Controller): OpportunityStageController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Opportunity Stage
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Opportunity Stage" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\OpportunityStage;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OpportunityStageController extends Controller
{
    /**
     * عرض قائمة سجلات (Opportunity Stage) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = OpportunityStage::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Opportunity Stage) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('opportunity_stage', 'create'));
        $opportunityStage = OpportunityStage::create($data);
        return response()->json($opportunityStage, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Opportunity Stage) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return OpportunityStage::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Opportunity Stage) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $opportunityStage = OpportunityStage::findOrFail($id);
        $data = $request->validate(ValidationRules::for('opportunity_stage', 'update', $opportunityStage));
        $opportunityStage->update($data);
        return $opportunityStage;
    }

    /**
     * حذف سجل من (Opportunity Stage) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $opportunityStage = OpportunityStage::findOrFail($id);
        $opportunityStage->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Opportunity Stage) وإعادته للعمل.
     */
    public function restore($id)
    {
        $opportunityStage = OpportunityStage::withTrashed()->findOrFail($id);
        $opportunityStage->restore();
        return $opportunityStage;
    }

    /**
     * حذف نهائي للسجل من (Opportunity Stage) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $opportunityStage = OpportunityStage::withTrashed()->findOrFail($id);
        $opportunityStage->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
