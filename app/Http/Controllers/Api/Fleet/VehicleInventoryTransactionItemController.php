<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleInventoryTransactionItemController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Inventory Transaction Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Inventory Transaction Item" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleInventoryTransactionItem;
use Illuminate\Http\Request;

class VehicleInventoryTransactionItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Inventory Transaction Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleInventoryTransactionItem::with(['item', 'unit']);

        if ($request->filled('vehicle_inventory_transaction_id')) {
            $query->where('vehicle_inventory_transaction_id', $request->vehicle_inventory_transaction_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Inventory Transaction Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_inventory_transaction_id' => 'required',
            'item_id' => 'required',
            'unit_id' => 'nullable',
            'qty' => 'required|numeric',
            'unit_cost' => 'nullable|numeric',
            'total_cost' => 'nullable|numeric',
        ]);

        $item = VehicleInventoryTransactionItem::create($validated);

        return response()->json($item->load(['item', 'unit']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Inventory Transaction Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = VehicleInventoryTransactionItem::with(['item', 'unit'])->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Inventory Transaction Item) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = VehicleInventoryTransactionItem::findOrFail($id);

        $validated = $request->validate([
            'vehicle_inventory_transaction_id' => 'sometimes|required',
            'item_id' => 'sometimes|required',
            'unit_id' => 'nullable',
            'qty' => 'sometimes|required|numeric',
            'unit_cost' => 'nullable|numeric',
            'total_cost' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item->load(['item', 'unit']));
    }

    /**
     * حذف سجل من (Vehicle Inventory Transaction Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleInventoryTransactionItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Vehicle inventory transaction item deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Inventory Transaction Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleInventoryTransactionItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item->load(['item', 'unit']));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Inventory Transaction Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleInventoryTransactionItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Vehicle inventory transaction item permanently deleted']);
    }
}
