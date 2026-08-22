<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleWorkOrderItemController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Work Order Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Work Order Item" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleWorkOrderItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleWorkOrderItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Work Order Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleWorkOrderItem::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%");
            });
        }

        if ($workOrderId = $request->input('vehicle_work_order_id')) {
            $query->where('vehicle_work_order_id', $workOrderId);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Work Order Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_work_order_item', 'create'));
        $item = VehicleWorkOrderItem::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleWorkOrderItem::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleWorkOrderItem::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_work_order_item', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Work Order Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleWorkOrderItem::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Work Order Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleWorkOrderItem::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Work Order Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleWorkOrderItem::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
