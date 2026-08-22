<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxJurisdictionController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Jurisdiction
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Jurisdiction" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxJurisdiction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxJurisdictionController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Jurisdiction) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaxJurisdiction::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('jurisdiction_code', 'like', "%{$search}%")
                  ->orWhere('jurisdiction_name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $jurisdictions = $query->paginate($perPage);

        return response()->json($jurisdictions);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Jurisdiction) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jurisdiction_code' => 'required',
            'jurisdiction_name' => 'required',
            'country_id' => 'required',
        ]);

        $jurisdiction = TaxJurisdiction::create($validated);

        return response()->json($jurisdiction, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Jurisdiction) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxJurisdiction $taxJurisdiction): JsonResponse
    {
        return response()->json($taxJurisdiction);
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Jurisdiction) بناءً على المعرّف.
     */
    public function update(Request $request, TaxJurisdiction $taxJurisdiction): JsonResponse
    {
        $validated = $request->validate([
            'jurisdiction_code' => 'required',
            'jurisdiction_name' => 'required',
            'country_id' => 'required',
        ]);

        $taxJurisdiction->update($validated);

        return response()->json($taxJurisdiction);
    }

    /**
     * حذف سجل من (Tax Jurisdiction) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxJurisdiction $taxJurisdiction): JsonResponse
    {
        $taxJurisdiction->delete();

        return response()->json(['message' => 'Tax jurisdiction deleted successfully']);
    }
}
