<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseRequestItemController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Request Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Request Item" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequestItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseRequestItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Request Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PurchaseRequestItem::with($with);
        if ($request->purchase_request_id) $query->where('purchase_request_id', $request->purchase_request_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('item', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Request Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('purchase_request_item', 'store'));
        $item = PurchaseRequestItem::create($data);
        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Request Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseRequestItem $purchaseRequestItem)
    {
        return $purchaseRequestItem->load(['purchaseRequest', 'item', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Request Item) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseRequestItem $purchaseRequestItem)
    {
        $data = $request->validate(ValidationRules::for('purchase_request_item', 'update', $purchaseRequestItem));
        $purchaseRequestItem->update($data);
        return response()->json($purchaseRequestItem);
    }

    /**
     * حذف سجل من (Purchase Request Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseRequestItem $purchaseRequestItem)
    {
        $purchaseRequestItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Request Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = PurchaseRequestItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Purchase Request Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PurchaseRequestItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Request Item).
     */
    public function schema()
    {
        return ValidationRules::for('purchase_request_item', 'store');
    }
}
