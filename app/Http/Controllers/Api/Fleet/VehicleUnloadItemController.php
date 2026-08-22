<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleUnloadItemController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Unload Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Unload Item" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleUnloadItem;
use Illuminate\Http\Request;

class VehicleUnloadItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Unload Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleUnloadItem::with(['item', 'unit']);

        if ($request->filled('vehicle_unload_id')) {
            $query->where('vehicle_unload_id', $request->vehicle_unload_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Unload Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_unload_id' => 'required',
            'item_id' => 'required',
            'unit_id' => 'nullable',
            'qty' => 'required|numeric',
            'cost' => 'nullable|numeric',
        ]);

        $item = VehicleUnloadItem::create($validated);

        return response()->json($item->load(['item', 'unit']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Unload Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = VehicleUnloadItem::with(['item', 'unit'])->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Unload Item) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = VehicleUnloadItem::findOrFail($id);

        $validated = $request->validate([
            'vehicle_unload_id' => 'sometimes|required',
            'item_id' => 'sometimes|required',
            'unit_id' => 'nullable',
            'qty' => 'sometimes|required|numeric',
            'cost' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item->load(['item', 'unit']));
    }

    /**
     * حذف سجل من (Vehicle Unload Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleUnloadItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Vehicle unload item deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Unload Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleUnloadItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item->load(['item', 'unit']));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Unload Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleUnloadItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Vehicle unload item permanently deleted']);
    }
}
