<?php
/**
 * =====================================================================
 * متحكم (Controller): WarehouseTypeController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Warehouse Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Warehouse Type" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\WarehouseType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WarehouseTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Warehouse Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = WarehouseType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Warehouse Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('warehouse_type', 'store'));
        $warehouseType = WarehouseType::create($data);

        return response()->json($warehouseType, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Warehouse Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(WarehouseType $warehouseType)
    {
        return $warehouseType;
    }

    /**
     * تحديث بيانات سجل موجود من (Warehouse Type) بناءً على المعرّف.
     */
    public function update(Request $request, WarehouseType $warehouseType)
    {
        $data = $request->validate(ValidationRules::for('warehouse_type', 'update', $warehouseType));
        $warehouseType->update($data);

        return response()->json($warehouseType);
    }

    /**
     * حذف سجل من (Warehouse Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(WarehouseType $warehouseType)
    {
        $warehouseType->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Warehouse Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $warehouseType = WarehouseType::onlyTrashed()->findOrFail($id);
        $warehouseType->restore();

        return response()->json($warehouseType);
    }

    /**
     * حذف نهائي للسجل من (Warehouse Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $warehouseType = WarehouseType::onlyTrashed()->findOrFail($id);
        $warehouseType->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Warehouse Type).
     */
    public function schema()
    {
        return ValidationRules::for('warehouse_type', 'store');
    }
}
