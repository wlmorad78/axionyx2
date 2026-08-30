<?php
/**
 * =====================================================================
 * متحكم (Controller): InventoryTransactionTypeController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Inventory Transaction Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Inventory Transaction Type" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransactionType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryTransactionTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Inventory Transaction Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryTransactionType::with($with);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Inventory Transaction Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction_type', 'store'));
        return response()->json(InventoryTransactionType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Inventory Transaction Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(InventoryTransactionType $inventoryTransactionType)
    {
        return $inventoryTransactionType->load([]);
    }

    /**
     * تحديث بيانات سجل موجود من (Inventory Transaction Type) بناءً على المعرّف.
     */
    public function update(Request $request, InventoryTransactionType $inventoryTransactionType)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction_type', 'update', $inventoryTransactionType));
        $inventoryTransactionType->update($data);
        return response()->json($inventoryTransactionType);
    }

    /**
     * حذف سجل من (Inventory Transaction Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(InventoryTransactionType $inventoryTransactionType)
    {
        $inventoryTransactionType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Inventory Transaction Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = InventoryTransactionType::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Inventory Transaction Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        InventoryTransactionType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Inventory Transaction Type).
     */
    public function schema()
    {
        return ValidationRules::for('inventory_transaction_type', 'store');
    }
}
