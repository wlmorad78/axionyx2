<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseReturnItemController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Return Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Return Item" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReturnItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseReturnItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Return Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = PurchaseReturnItem::with(['item', 'unit']);

        if ($request->filled('purchase_return_id')) {
            $query->where('purchase_return_id', $request->purchase_return_id);
        }
        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Return Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(ValidationRules::for('purchase_return_item', 'store'));
        $item = PurchaseReturnItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Return Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseReturnItem $purchaseReturnItem)
    {
        $purchaseReturnItem->load(['item', 'unit', 'purchaseReturn']);

        return response()->json($purchaseReturnItem);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Return Item) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseReturnItem $purchaseReturnItem)
    {
        $validated = $request->validate(ValidationRules::for('purchase_return_item', 'update', $purchaseReturnItem));
        $purchaseReturnItem->update($validated);

        return response()->json($purchaseReturnItem);
    }

    /**
     * حذف سجل من (Purchase Return Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseReturnItem $purchaseReturnItem)
    {
        $purchaseReturnItem->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Return Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = PurchaseReturnItem::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Purchase Return Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PurchaseReturnItem::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Return Item).
     */
    public function schema()
    {
        return ValidationRules::for('purchase_return_item', 'store');
    }
}
