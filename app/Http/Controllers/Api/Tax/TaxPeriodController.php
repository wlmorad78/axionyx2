<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxPeriodController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Period
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Period" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxPeriod;
use Illuminate\Http\Request;

class TaxPeriodController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Period) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TaxPeriod::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('period_name', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $taxPeriods = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($taxPeriods);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Period) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'period_name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'required|in:OPEN,CLOSED,SUBMITTED',
        ]);

        $taxPeriod = TaxPeriod::create($validated);

        return response()->json($taxPeriod, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Period) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxPeriod $taxPeriod)
    {
        return response()->json($taxPeriod);
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Period) بناءً على المعرّف.
     */
    public function update(Request $request, TaxPeriod $taxPeriod)
    {
        $validated = $request->validate([
            'period_name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'required|in:OPEN,CLOSED,SUBMITTED',
        ]);

        $taxPeriod->update($validated);

        return response()->json($taxPeriod);
    }

    /**
     * حذف سجل من (Tax Period) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxPeriod $taxPeriod)
    {
        $taxPeriod->delete();

        return response()->json(['message' => 'Tax period deleted successfully.']);
    }
}
