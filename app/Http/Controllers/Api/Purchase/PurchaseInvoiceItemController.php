<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseInvoiceItemController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Invoice Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Invoice Item" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoiceItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseInvoiceItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Invoice Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PurchaseInvoiceItem::with(['item', 'unit']);

        if ($request->filled('purchase_invoice_id')) {
            $query->where('purchase_invoice_id', $request->purchase_invoice_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Invoice Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_invoice_item', 'store'));
        $item = PurchaseInvoiceItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Invoice Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseInvoiceItem $purchaseInvoiceItem)
    {
        $purchaseInvoiceItem->load(['item', 'unit', 'purchaseInvoice']);

        return response()->json($purchaseInvoiceItem);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Invoice Item) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseInvoiceItem $purchaseInvoiceItem)
    {
        $validated = $request->validate(ValidationRules::for('purchase_invoice_item', 'update', $purchaseInvoiceItem));
        $purchaseInvoiceItem->update($validated);

        return response()->json($purchaseInvoiceItem);
    }

    /**
     * حذف سجل من (Purchase Invoice Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseInvoiceItem $purchaseInvoiceItem)
    {
        $purchaseInvoiceItem->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Invoice Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = PurchaseInvoiceItem::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Purchase Invoice Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PurchaseInvoiceItem::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Invoice Item).
     */
    public function schema()
    {
        return ValidationRules::for('purchase_invoice_item', 'store');
    }
}
