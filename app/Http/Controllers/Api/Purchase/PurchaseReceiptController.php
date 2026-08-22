<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseReceiptController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Receipt
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Receipt" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseReceipt;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseReceiptController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Receipt) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PurchaseReceipt::with(['supplier', 'warehouse', 'createdByEmployee']);

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
            $query->where('receipt_no', 'like', '%' . $request->search . '%');
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Receipt) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_receipt', 'store'));
        $receipt = PurchaseReceipt::create($validated);

        return response()->json($receipt, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Receipt) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseReceipt $purchaseReceipt)
    {
        $purchaseReceipt->load(['supplier', 'purchaseOrder', 'items.item', 'items.unit', 'warehouse', 'createdByEmployee']);

        return response()->json($purchaseReceipt);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Receipt) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseReceipt $purchaseReceipt)
    {
        $validated = $request->validate(ValidationRules::for('purchase_receipt', 'update', $purchaseReceipt));
        $purchaseReceipt->update($validated);

        return response()->json($purchaseReceipt);
    }

    /**
     * حذف سجل من (Purchase Receipt) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseReceipt $purchaseReceipt)
    {
        $purchaseReceipt->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Receipt) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = PurchaseReceipt::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Purchase Receipt) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PurchaseReceipt::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Receipt).
     */
    public function schema()
    {
        return ValidationRules::for('purchase_receipt', 'store');
    }
}
