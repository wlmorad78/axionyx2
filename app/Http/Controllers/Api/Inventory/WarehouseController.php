<?php
/**
 * =====================================================================
 * متحكم (Controller): WarehouseController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Warehouse
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Warehouse" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * عرض قائمة سجلات (Warehouse) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Warehouse::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Warehouse) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('warehouse', 'store'));
        $warehouse = Warehouse::create($data);
        return response()->json($warehouse, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Warehouse) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Warehouse $warehouse)
    {
        return $warehouse->load(['company', 'branch', 'warehouseType', 'manager']);
    }

    /**
     * تحديث بيانات سجل موجود من (Warehouse) بناءً على المعرّف.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate(ValidationRules::for('warehouse', 'update', $warehouse));
        $warehouse->update($data);
        return response()->json($warehouse);
    }

    /**
     * حذف سجل من (Warehouse) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Warehouse) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $warehouse = Warehouse::onlyTrashed()->findOrFail($id);
        $warehouse->restore();
        return response()->json($warehouse);
    }

    /**
     * حذف نهائي للسجل من (Warehouse) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $warehouse = Warehouse::onlyTrashed()->findOrFail($id);
        $warehouse->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Warehouse).
     */
    public function schema()
    {
        return ValidationRules::for('warehouse', 'store');
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Warehouse).
     */
    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = Warehouse::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $lastCode = $query->orderByRaw("CAST(SUBSTR(code, 4) AS INTEGER) DESC")->value('code');
        if ($lastCode && preg_match('/^WH-(\d+)$/', $lastCode, $m)) {
            $next = intval($m[1]) + 1;
        } else {
            $next = 1;
        }
        return response()->json(['code' => 'WH-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }
}
