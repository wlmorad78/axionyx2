<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleSettlementItemController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Settlement Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Settlement Item" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleSettlementItem;
use Illuminate\Http\Request;

class VehicleSettlementItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Settlement Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleSettlementItem::with(['item']);

        if ($request->filled('vehicle_settlement_id')) {
            $query->where('vehicle_settlement_id', $request->vehicle_settlement_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Settlement Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_settlement_id' => 'required',
            'item_id' => 'required',
            'opening_qty' => 'nullable|numeric',
            'loaded_qty' => 'nullable|numeric',
            'sold_qty' => 'nullable|numeric',
            'returned_qty' => 'nullable|numeric',
            'closing_qty' => 'nullable|numeric',
            'variance_qty' => 'nullable|numeric',
        ]);

        $item = VehicleSettlementItem::create($validated);

        return response()->json($item->load('item'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Settlement Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = VehicleSettlementItem::with(['item'])->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Settlement Item) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = VehicleSettlementItem::findOrFail($id);

        $validated = $request->validate([
            'vehicle_settlement_id' => 'required',
            'item_id' => 'required',
            'opening_qty' => 'nullable|numeric',
            'loaded_qty' => 'nullable|numeric',
            'sold_qty' => 'nullable|numeric',
            'returned_qty' => 'nullable|numeric',
            'closing_qty' => 'nullable|numeric',
            'variance_qty' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item->load('item'));
    }

    /**
     * حذف سجل من (Vehicle Settlement Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleSettlementItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Vehicle settlement item deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Settlement Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleSettlementItem::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item->load('item'));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Settlement Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleSettlementItem::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Vehicle settlement item permanently deleted']);
    }
}
