<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseReceiptItemController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Receipt Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Receipt Item" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReceiptItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseReceiptItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Receipt Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PurchaseReceiptItem::with(['item', 'unit']);

        if ($request->filled('purchase_receipt_id')) {
            $query->where('purchase_receipt_id', $request->purchase_receipt_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Receipt Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_receipt_item', 'store'));
        $item = PurchaseReceiptItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Receipt Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseReceiptItem $purchaseReceiptItem)
    {
        $purchaseReceiptItem->load(['item', 'unit', 'purchaseReceipt']);

        return response()->json($purchaseReceiptItem);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Receipt Item) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseReceiptItem $purchaseReceiptItem)
    {
        $validated = $request->validate(ValidationRules::for('purchase_receipt_item', 'update', $purchaseReceiptItem));
        $purchaseReceiptItem->update($validated);

        return response()->json($purchaseReceiptItem);
    }

    /**
     * حذف سجل من (Purchase Receipt Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseReceiptItem $purchaseReceiptItem)
    {
        $purchaseReceiptItem->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Receipt Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = PurchaseReceiptItem::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Purchase Receipt Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PurchaseReceiptItem::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Receipt Item).
     */
    public function schema()
    {
        return ValidationRules::for('purchase_receipt_item', 'store');
    }
}
