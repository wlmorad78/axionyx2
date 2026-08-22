<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseReturnController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Return
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Return" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseReturn;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Return) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PurchaseReturn::with(['supplier', 'purchaseInvoice', 'createdByEmployee']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('return_no', 'like', '%' . $request->search . '%');
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Return) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_return', 'store'));
        $return = PurchaseReturn::create($validated);

        return response()->json($return, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Return) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['supplier', 'purchaseInvoice', 'items.item', 'items.unit', 'warehouse', 'createdByEmployee']);

        return response()->json($purchaseReturn);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Return) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseReturn $purchaseReturn)
    {
        $validated = $request->validate(ValidationRules::for('purchase_return', 'update', $purchaseReturn));
        $purchaseReturn->update($validated);

        return response()->json($purchaseReturn);
    }

    /**
     * حذف سجل من (Purchase Return) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Return) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = PurchaseReturn::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Purchase Return) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PurchaseReturn::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Return).
     */
    public function schema()
    {
        return ValidationRules::for('purchase_return', 'store');
    }
}
