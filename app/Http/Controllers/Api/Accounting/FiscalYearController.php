<?php
/**
 * =====================================================================
 * متحكم (Controller): FiscalYearController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Fiscal Year
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Fiscal Year" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    /**
     * عرض قائمة سجلات (Fiscal Year) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = FiscalYear::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
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
     * إنشاء سجل جديد لـ (Fiscal Year) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('fiscal_year', 'store'));
        return response()->json(FiscalYear::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Fiscal Year) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(FiscalYear $fiscalYear)
    {
        return $fiscalYear->load(['company', 'accountingPeriods', 'journalEntries']);
    }

    /**
     * تحديث بيانات سجل موجود من (Fiscal Year) بناءً على المعرّف.
     */
    public function update(Request $request, FiscalYear $fiscalYear)
    {
        $data = $request->validate(ValidationRules::for('fiscal_year', 'update', $fiscalYear));
        $fiscalYear->update($data);
        return response()->json($fiscalYear);
    }

    /**
     * حذف سجل من (Fiscal Year) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(FiscalYear $fiscalYear)
    {
        $fiscalYear->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Fiscal Year) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = FiscalYear::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Fiscal Year) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        FiscalYear::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Fiscal Year).
     */
    public function schema()
    {
        return ValidationRules::for('fiscal_year', 'store');
    }
}
