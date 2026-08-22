<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxCalculationDetailController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Calculation Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Calculation Detail" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxCalculationDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxCalculationDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Calculation Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaxCalculationDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('tax_calculation_id', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $details = $query->paginate($perPage);

        return response()->json($details);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Calculation Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tax_calculation_id' => 'required',
            'tax_type_id' => 'required',
            'tax_rate' => 'numeric',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
        ]);

        $detail = TaxCalculationDetail::create($validated);

        return response()->json($detail, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Calculation Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxCalculationDetail $taxCalculationDetail): JsonResponse
    {
        return response()->json($taxCalculationDetail);
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Calculation Detail) بناءً على المعرّف.
     */
    public function update(Request $request, TaxCalculationDetail $taxCalculationDetail): JsonResponse
    {
        $validated = $request->validate([
            'tax_calculation_id' => 'required',
            'tax_type_id' => 'required',
            'tax_rate' => 'numeric',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
        ]);

        $taxCalculationDetail->update($validated);

        return response()->json($taxCalculationDetail);
    }

    /**
     * حذف سجل من (Tax Calculation Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxCalculationDetail $taxCalculationDetail): JsonResponse
    {
        $taxCalculationDetail->delete();

        return response()->json(['message' => 'Tax calculation detail deleted successfully']);
    }
}
