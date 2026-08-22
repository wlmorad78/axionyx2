<?php
/**
 * =====================================================================
 * متحكم (Controller): BankReconciliationController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Bank Reconciliation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Bank Reconciliation" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\BankReconciliation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    /**
     * عرض قائمة سجلات (Bank Reconciliation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = BankReconciliation::with($with);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->bank_account_id) $query->where('bank_account_id', $request->bank_account_id);
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
     * إنشاء سجل جديد لـ (Bank Reconciliation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('bank_reconciliation', 'store'));
        return response()->json(BankReconciliation::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Bank Reconciliation) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(BankReconciliation $bankReconciliation)
    {
        return $bankReconciliation->load(['bankAccount', 'company', 'branch', 'createdByEmployee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Bank Reconciliation) بناءً على المعرّف.
     */
    public function update(Request $request, BankReconciliation $bankReconciliation)
    {
        $data = $request->validate(ValidationRules::for('bank_reconciliation', 'update', $bankReconciliation));
        $bankReconciliation->update($data);
        return response()->json($bankReconciliation);
    }

    /**
     * حذف سجل من (Bank Reconciliation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Bank Reconciliation) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = BankReconciliation::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Bank Reconciliation) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        BankReconciliation::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Bank Reconciliation).
     */
    public function schema()
    {
        return ValidationRules::for('bank_reconciliation', 'store');
    }
}
