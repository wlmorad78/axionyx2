<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxReturnDetailController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Return Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Return Detail" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxReturnDetail;
use Illuminate\Http\Request;

class TaxReturnDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Return Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TaxReturnDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('tax_return_id', $search);
        }

        $taxReturnDetails = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($taxReturnDetails);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Return Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tax_return_id' => 'required',
            'tax_type_id' => 'required',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
        ]);

        $taxReturnDetail = TaxReturnDetail::create($validated);

        return response()->json($taxReturnDetail, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Return Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxReturnDetail $taxReturnDetail)
    {
        return response()->json($taxReturnDetail);
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Return Detail) بناءً على المعرّف.
     */
    public function update(Request $request, TaxReturnDetail $taxReturnDetail)
    {
        $validated = $request->validate([
            'tax_return_id' => 'required',
            'tax_type_id' => 'required',
            'taxable_amount' => 'numeric',
            'tax_amount' => 'numeric',
        ]);

        $taxReturnDetail->update($validated);

        return response()->json($taxReturnDetail);
    }

    /**
     * حذف سجل من (Tax Return Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxReturnDetail $taxReturnDetail)
    {
        $taxReturnDetail->delete();

        return response()->json(['message' => 'Tax return detail deleted successfully.']);
    }
}
