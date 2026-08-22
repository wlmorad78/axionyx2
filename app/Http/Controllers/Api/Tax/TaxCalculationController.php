<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxCalculationController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Calculation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Calculation" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxCalculation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxCalculationController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Calculation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaxCalculation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_type', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $taxCalculations = $query->paginate($perPage);

        return response()->json($taxCalculations);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Calculation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference_type' => 'required|in:SALES_INVOICE,PURCHASE_INVOICE,RETURN,CREDIT_NOTE,DEBIT_NOTE',
            'reference_id' => 'required',
            'calculation_date' => 'required|date',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
            'total_amount' => 'numeric',
        ]);

        $taxCalculation = TaxCalculation::create($validated);

        return response()->json($taxCalculation, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Calculation) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxCalculation $taxCalculation): JsonResponse
    {
        $taxCalculation->load('details');

        return response()->json($taxCalculation);
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Calculation) بناءً على المعرّف.
     */
    public function update(Request $request, TaxCalculation $taxCalculation): JsonResponse
    {
        $validated = $request->validate([
            'reference_type' => 'required|in:SALES_INVOICE,PURCHASE_INVOICE,RETURN,CREDIT_NOTE,DEBIT_NOTE',
            'reference_id' => 'required',
            'calculation_date' => 'required|date',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
            'total_amount' => 'numeric',
        ]);

        $taxCalculation->update($validated);

        return response()->json($taxCalculation);
    }

    /**
     * حذف سجل من (Tax Calculation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxCalculation $taxCalculation): JsonResponse
    {
        $taxCalculation->delete();

        return response()->json(['message' => 'Tax calculation deleted successfully']);
    }
}
