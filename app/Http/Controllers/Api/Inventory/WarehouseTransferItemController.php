<?php
/**
 * =====================================================================
 * متحكم (Controller): WarehouseTransferItemController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Warehouse Transfer Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Warehouse Transfer Item" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\WarehouseTransferItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WarehouseTransferItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Warehouse Transfer Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = WarehouseTransferItem::with($with);
        if ($request->warehouse_transfer_id) $query->where('warehouse_transfer_id', $request->warehouse_transfer_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%")->orWhere('batch_no', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Warehouse Transfer Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('warehouse_transfer_item', 'store'));
        return response()->json(WarehouseTransferItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Warehouse Transfer Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(WarehouseTransferItem $warehouseTransferItem)
    {
        return $warehouseTransferItem->load([
            'warehouseTransfer', 'item', 'unit', 'batch',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Warehouse Transfer Item) بناءً على المعرّف.
     */
    public function update(Request $request, WarehouseTransferItem $warehouseTransferItem)
    {
        $data = $request->validate(ValidationRules::for('warehouse_transfer_item', 'update', $warehouseTransferItem));
        $warehouseTransferItem->update($data);
        return response()->json($warehouseTransferItem);
    }

    /**
     * حذف سجل من (Warehouse Transfer Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(WarehouseTransferItem $warehouseTransferItem)
    {
        $warehouseTransferItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Warehouse Transfer Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = WarehouseTransferItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Warehouse Transfer Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        WarehouseTransferItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Warehouse Transfer Item).
     */
    public function schema()
    {
        return ValidationRules::for('warehouse_transfer_item', 'store');
    }
}
