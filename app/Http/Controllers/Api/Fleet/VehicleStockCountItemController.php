<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleStockCountItemController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Stock Count Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Stock Count Item" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleStockCountItem;
use Illuminate\Http\Request;

class VehicleStockCountItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Stock Count Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleStockCountItem::with(['item']);

        if ($request->filled('vehicle_stock_count_id')) {
            $query->where('vehicle_stock_count_id', $request->vehicle_stock_count_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Stock Count Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_stock_count_id' => 'required',
            'item_id' => 'required',
            'system_qty' => 'nullable|numeric',
            'actual_qty' => 'nullable|numeric',
            'variance_qty' => 'nullable|numeric',
        ]);

        $item = VehicleStockCountItem::create($validated);

        return response()->json($item->load('item'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Stock Count Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = VehicleStockCountItem::with(['item'])->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Stock Count Item) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = VehicleStockCountItem::findOrFail($id);

        $validated = $request->validate([
            'vehicle_stock_count_id' => 'required',
            'item_id' => 'required',
            'system_qty' => 'nullable|numeric',
            'actual_qty' => 'nullable|numeric',
            'variance_qty' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item->load('item'));
    }

    /**
     * حذف سجل من (Vehicle Stock Count Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleStockCountItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Vehicle stock count item deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Stock Count Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleStockCountItem::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item->load('item'));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Stock Count Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleStockCountItem::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Vehicle stock count item permanently deleted']);
    }
}
