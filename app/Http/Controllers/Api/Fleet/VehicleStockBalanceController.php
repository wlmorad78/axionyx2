<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleStockBalanceController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Stock Balance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Stock Balance" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleStockBalance;
use Illuminate\Http\Request;

class VehicleStockBalanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Stock Balance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleStockBalance::with(['item', 'vehicleWarehouse']);

        if ($request->filled('vehicle_warehouse_id')) {
            $query->where('vehicle_warehouse_id', $request->vehicle_warehouse_id);
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        $balances = $query->paginate($request->get('per_page', 15));

        return response()->json($balances);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Stock Balance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_warehouse_id' => 'required',
            'item_id' => 'required',
            'qty' => 'nullable|numeric',
            'average_cost' => 'nullable|numeric',
            'stock_value' => 'nullable|numeric',
        ]);

        $balance = VehicleStockBalance::create($validated);

        return response()->json($balance->load(['item', 'vehicleWarehouse']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Stock Balance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $balance = VehicleStockBalance::with(['item', 'vehicleWarehouse'])->findOrFail($id);

        return response()->json($balance);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Stock Balance) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $balance = VehicleStockBalance::findOrFail($id);

        $validated = $request->validate([
            'vehicle_warehouse_id' => 'sometimes|required',
            'item_id' => 'sometimes|required',
            'qty' => 'nullable|numeric',
            'average_cost' => 'nullable|numeric',
            'stock_value' => 'nullable|numeric',
        ]);

        $balance->update($validated);

        return response()->json($balance->load(['item', 'vehicleWarehouse']));
    }

    /**
     * حذف سجل من (Vehicle Stock Balance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $balance = VehicleStockBalance::findOrFail($id);
        $balance->delete();

        return response()->json(['message' => 'Vehicle stock balance deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Stock Balance) وإعادته للعمل.
     */
    public function restore($id)
    {
        $balance = VehicleStockBalance::onlyTrashed()->findOrFail($id);
        $balance->restore();

        return response()->json($balance->load(['item', 'vehicleWarehouse']));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Stock Balance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $balance = VehicleStockBalance::onlyTrashed()->findOrFail($id);
        $balance->forceDelete();

        return response()->json(['message' => 'Vehicle stock balance permanently deleted']);
    }
}
