<?php
/**
 * =====================================================================
 * متحكم (Controller): OpeningBalanceController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Opening Balance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Opening Balance" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\OpeningBalance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OpeningBalanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Opening Balance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = OpeningBalance::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->account_id) $query->where('account_id', $request->account_id);
        if ($request->fiscal_year_id) $query->where('fiscal_year_id', $request->fiscal_year_id);
        if ($request->accounting_period_id) $query->where('accounting_period_id', $request->accounting_period_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Opening Balance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('opening_balance', 'store'));
        return response()->json(OpeningBalance::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Opening Balance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(OpeningBalance $openingBalance)
    {
        return $openingBalance->load(['account', 'company', 'branch', 'fiscalYear', 'accountingPeriod', 'createdByEmployee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Opening Balance) بناءً على المعرّف.
     */
    public function update(Request $request, OpeningBalance $openingBalance)
    {
        $data = $request->validate(ValidationRules::for('opening_balance', 'update', $openingBalance));
        $openingBalance->update($data);
        return response()->json($openingBalance);
    }

    /**
     * حذف سجل من (Opening Balance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(OpeningBalance $openingBalance)
    {
        $openingBalance->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Opening Balance) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = OpeningBalance::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Opening Balance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        OpeningBalance::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Opening Balance).
     */
    public function schema()
    {
        return ValidationRules::for('opening_balance', 'store');
    }
}
