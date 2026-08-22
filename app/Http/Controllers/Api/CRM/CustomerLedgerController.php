<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerLedgerController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Ledger
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Ledger" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerLedger;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerLedgerController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Ledger) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerLedger::with($with);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->customer_id) $query->where('customer_id', $request->customer_id);
        if ($request->account_id) $query->where('account_id', $request->account_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Ledger) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_ledger', 'store'));
        return response()->json(CustomerLedger::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Ledger) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerLedger $customerLedger)
    {
        return $customerLedger->load(['customer', 'account', 'company', 'branch', 'createdByEmployee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Ledger) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerLedger $customerLedger)
    {
        $data = $request->validate(ValidationRules::for('customer_ledger', 'update', $customerLedger));
        $customerLedger->update($data);
        return response()->json($customerLedger);
    }

    /**
     * حذف سجل من (Customer Ledger) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerLedger $customerLedger)
    {
        $customerLedger->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Ledger) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = CustomerLedger::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Customer Ledger) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerLedger::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Ledger).
     */
    public function schema()
    {
        return ValidationRules::for('customer_ledger', 'store');
    }
}
