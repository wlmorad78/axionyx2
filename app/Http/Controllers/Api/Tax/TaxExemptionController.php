<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxExemptionController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Exemption
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Exemption" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxExemption;
use Illuminate\Http\Request;

class TaxExemptionController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Exemption) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TaxExemption::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('exemption_code', 'like', "%{$search}%")
                  ->orWhere('exemption_name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $taxExemptions = $query->paginate($perPage);

        return response()->json($taxExemptions);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Exemption) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'exemption_code' => 'required',
            'exemption_name' => 'required',
            'effective_from' => 'required|date',
        ]);

        $taxExemption = TaxExemption::create($validated);

        return response()->json($taxExemption, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Exemption) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxExemption $taxExemption)
    {
        return response()->json($taxExemption);
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Exemption) بناءً على المعرّف.
     */
    public function update(Request $request, TaxExemption $taxExemption)
    {
        $validated = $request->validate([
            'exemption_code' => 'sometimes|required',
            'exemption_name' => 'sometimes|required',
            'effective_from' => 'sometimes|required|date',
        ]);

        $taxExemption->update($validated);

        return response()->json($taxExemption);
    }

    /**
     * حذف سجل من (Tax Exemption) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxExemption $taxExemption)
    {
        $taxExemption->delete();

        return response()->json(null, 204);
    }
}
