<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryOpeningBalanceController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Opening Balance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Opening Balance" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryOpeningBalance;
use App\Services\CompanyContext;
use Illuminate\Http\Request;

class TreasuryOpeningBalanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Opening Balance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $companyId = CompanyContext::id();
        $with = $request->with ? explode(',', $request->with) : ['treasury', 'fiscalYear'];
        $query = TreasuryOpeningBalance::with($with);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->treasury_id) {
            $query->where('treasury_id', $request->treasury_id);
        }
        if ($request->fiscal_year_id) {
            $query->where('fiscal_year_id', $request->fiscal_year_id);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 500);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Opening Balance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $companyId = CompanyContext::id();

        $data = $request->validate([
            'treasury_id' => 'required',
            'fiscal_year_id' => 'nullable',
            'opening_balance' => 'nullable|numeric',
        ]);

        $data['company_id'] = $companyId;

        $openingBalance = TreasuryOpeningBalance::create($data);

        // Update treasury opening_balance
        \App\Models\Treasury::where('id', $data['treasury_id'])->update([
            'opening_balance' => $data['opening_balance'] ?? 0,
        ]);

        return response()->json($openingBalance->load(['treasury', 'fiscalYear']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Opening Balance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $openingBalance = TreasuryOpeningBalance::with(['treasury', 'fiscalYear'])->findOrFail($id);
        return response()->json($openingBalance);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Opening Balance) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $openingBalance = TreasuryOpeningBalance::findOrFail($id);

        $data = $request->validate([
            'treasury_id' => 'required',
            'fiscal_year_id' => 'nullable',
            'opening_balance' => 'nullable|numeric',
        ]);

        $openingBalance->update($data);

        // Update treasury opening_balance
        \App\Models\Treasury::where('id', $data['treasury_id'])->update([
            'opening_balance' => $data['opening_balance'] ?? 0,
        ]);

        return response()->json($openingBalance->load(['treasury', 'fiscalYear']));
    }

    /**
     * حذف سجل من (Treasury Opening Balance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $openingBalance = TreasuryOpeningBalance::findOrFail($id);

        // Reset treasury opening_balance
        \App\Models\Treasury::where('id', $openingBalance->treasury_id)->update([
            'opening_balance' => 0,
        ]);

        $openingBalance->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Opening Balance) وإعادته للعمل.
     */
    public function restore($id)
    {
        $openingBalance = TreasuryOpeningBalance::onlyTrashed()->findOrFail($id);
        $openingBalance->restore();
        return response()->json($openingBalance);
    }

    /**
     * حذف نهائي للسجل من (Treasury Opening Balance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $openingBalance = TreasuryOpeningBalance::onlyTrashed()->findOrFail($id);
        $openingBalance->forceDelete();
        return response()->json(null, 204);
    }
}
