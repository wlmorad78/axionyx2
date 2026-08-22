<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleLoadItemController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Load Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Load Item" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleLoadItem;
use Illuminate\Http\Request;

class VehicleLoadItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Load Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleLoadItem::with(['item', 'unit']);

        if ($request->filled('vehicle_load_id')) {
            $query->where('vehicle_load_id', $request->vehicle_load_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Load Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_load_id' => 'required',
            'item_id' => 'required',
            'unit_id' => 'nullable',
            'qty' => 'required|numeric',
            'cost' => 'nullable|numeric',
        ]);

        $item = VehicleLoadItem::create($validated);

        return response()->json($item->load(['item', 'unit']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Load Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = VehicleLoadItem::with(['item', 'unit'])->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Load Item) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = VehicleLoadItem::findOrFail($id);

        $validated = $request->validate([
            'vehicle_load_id' => 'sometimes|required',
            'item_id' => 'sometimes|required',
            'unit_id' => 'nullable',
            'qty' => 'sometimes|required|numeric',
            'cost' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item->load(['item', 'unit']));
    }

    /**
     * حذف سجل من (Vehicle Load Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleLoadItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Vehicle load item deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Load Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleLoadItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item->load(['item', 'unit']));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Load Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleLoadItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Vehicle load item permanently deleted']);
    }
}
