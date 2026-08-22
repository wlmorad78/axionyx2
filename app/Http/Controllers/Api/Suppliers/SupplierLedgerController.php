<?php
/**
 * =====================================================================
 * متحكم (Controller): SupplierLedgerController
 * الوحدة (Module): الموردين (Suppliers)
 * المورد (Resource): Supplier Ledger
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Supplier Ledger" ضمن وحدة "الموردين".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\SupplierLedger;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierLedgerController extends Controller
{
    /**
     * عرض قائمة سجلات (Supplier Ledger) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierLedger::with($with);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
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
     * إنشاء سجل جديد لـ (Supplier Ledger) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_ledger', 'store'));
        return response()->json(SupplierLedger::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Supplier Ledger) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SupplierLedger $supplierLedger)
    {
        return $supplierLedger->load(['supplier', 'account', 'company', 'branch', 'createdByEmployee']);
    }

    /**
     * تحديث بيانات سجل موجود من (Supplier Ledger) بناءً على المعرّف.
     */
    public function update(Request $request, SupplierLedger $supplierLedger)
    {
        $data = $request->validate(ValidationRules::for('supplier_ledger', 'update', $supplierLedger));
        $supplierLedger->update($data);
        return response()->json($supplierLedger);
    }

    /**
     * حذف سجل من (Supplier Ledger) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SupplierLedger $supplierLedger)
    {
        $supplierLedger->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Supplier Ledger) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = SupplierLedger::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Supplier Ledger) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SupplierLedger::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Supplier Ledger).
     */
    public function schema()
    {
        return ValidationRules::for('supplier_ledger', 'store');
    }
}
