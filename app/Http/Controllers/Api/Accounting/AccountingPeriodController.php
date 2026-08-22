<?php
/**
 * =====================================================================
 * متحكم (Controller): AccountingPeriodController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Accounting Period
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Accounting Period" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AccountingPeriodController extends Controller
{
    /**
     * عرض قائمة سجلات (Accounting Period) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AccountingPeriod::with($with);
        if ($request->fiscal_year_id) $query->where('fiscal_year_id', $request->fiscal_year_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Accounting Period) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('accounting_period', 'store'));
        return response()->json(AccountingPeriod::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Accounting Period) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(AccountingPeriod $accountingPeriod)
    {
        return $accountingPeriod->load(['fiscalYear', 'company', 'journalEntries', 'openingBalances']);
    }

    /**
     * تحديث بيانات سجل موجود من (Accounting Period) بناءً على المعرّف.
     */
    public function update(Request $request, AccountingPeriod $accountingPeriod)
    {
        $data = $request->validate(ValidationRules::for('accounting_period', 'update', $accountingPeriod));
        $accountingPeriod->update($data);
        return response()->json($accountingPeriod);
    }

    /**
     * حذف سجل من (Accounting Period) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(AccountingPeriod $accountingPeriod)
    {
        $accountingPeriod->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Accounting Period) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = AccountingPeriod::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Accounting Period) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        AccountingPeriod::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Accounting Period).
     */
    public function schema()
    {
        return ValidationRules::for('accounting_period', 'store');
    }
}
