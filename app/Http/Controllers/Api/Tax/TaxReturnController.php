<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxReturnController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Return
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Return" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxReturn;
use Illuminate\Http\Request;

class TaxReturnController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Return) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TaxReturn::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $taxReturns = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($taxReturns);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Return) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'return_no' => 'required',
            'tax_period_id' => 'required',
            'total_sales' => 'numeric',
            'total_purchases' => 'numeric',
            'output_tax' => 'numeric',
            'input_tax' => 'numeric',
            'net_tax' => 'numeric',
            'status' => 'required|in:DRAFT,SUBMITTED,APPROVED',
        ]);

        $taxReturn = TaxReturn::create($validated);

        return response()->json($taxReturn, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Return) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxReturn $taxReturn)
    {
        $taxReturn->load('details');

        return response()->json($taxReturn);
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Return) بناءً على المعرّف.
     */
    public function update(Request $request, TaxReturn $taxReturn)
    {
        $validated = $request->validate([
            'return_no' => 'required',
            'tax_period_id' => 'required',
            'total_sales' => 'numeric',
            'total_purchases' => 'numeric',
            'output_tax' => 'numeric',
            'input_tax' => 'numeric',
            'net_tax' => 'numeric',
            'status' => 'required|in:DRAFT,SUBMITTED,APPROVED',
        ]);

        $taxReturn->update($validated);

        return response()->json($taxReturn);
    }

    /**
     * حذف سجل من (Tax Return) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxReturn $taxReturn)
    {
        $taxReturn->delete();

        return response()->json(['message' => 'Tax return deleted successfully.']);
    }
}
