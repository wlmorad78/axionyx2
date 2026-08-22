<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxRuleController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Rule" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaxRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('rule_name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $taxRules = $query->paginate($perPage);

        return response()->json($taxRules);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rule_name' => 'required',
            'tax_group_id' => 'required',
            'effective_from' => 'required|date',
            'priority' => 'integer',
        ]);

        $taxRule = TaxRule::create($validated);

        return response()->json($taxRule, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxRule $taxRule): JsonResponse
    {
        return response()->json($taxRule);
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Rule) بناءً على المعرّف.
     */
    public function update(Request $request, TaxRule $taxRule): JsonResponse
    {
        $validated = $request->validate([
            'rule_name' => 'required',
            'tax_group_id' => 'required',
            'effective_from' => 'required|date',
            'priority' => 'integer',
        ]);

        $taxRule->update($validated);

        return response()->json($taxRule);
    }

    /**
     * حذف سجل من (Tax Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxRule $taxRule): JsonResponse
    {
        $taxRule->delete();

        return response()->json(['message' => 'Tax rule deleted successfully']);
    }
}
