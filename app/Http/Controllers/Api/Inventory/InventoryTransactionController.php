<?php
/**
 * =====================================================================
 * متحكم (Controller): InventoryTransactionController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Inventory Transaction
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Inventory Transaction" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryTransactionController extends Controller
{
    /**
     * عرض قائمة سجلات (Inventory Transaction) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryTransaction::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->transaction_type_id) $query->where('transaction_type_id', $request->transaction_type_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transaction_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Inventory Transaction) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction', 'store'));
        if (empty($data['transaction_no'])) {
            $data['transaction_no'] = self::generateNextCode('INV', 'inventory_transactions', 'transaction_no');
        }
        return response()->json(InventoryTransaction::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Inventory Transaction) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(InventoryTransaction $inventoryTransaction)
    {
        return $inventoryTransaction->load([
            'company', 'branch', 'warehouse', 'transactionType',
            'items.item', 'items.unit', 'createdByEmployee',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Inventory Transaction) بناءً على المعرّف.
     */
    public function update(Request $request, InventoryTransaction $inventoryTransaction)
    {
        $data = $request->validate(ValidationRules::for('inventory_transaction', 'update', $inventoryTransaction));
        $inventoryTransaction->update($data);
        return response()->json($inventoryTransaction);
    }

    /**
     * حذف سجل من (Inventory Transaction) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(InventoryTransaction $inventoryTransaction)
    {
        $inventoryTransaction->delete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Inventory Transaction).
     */
    public function nextCode()
    {
        return response()->json(['code' => self::generateNextCode('INV', 'inventory_transactions', 'transaction_no')]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Inventory Transaction) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = InventoryTransaction::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Inventory Transaction) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        InventoryTransaction::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Inventory Transaction).
     */
    public function schema()
    {
        return ValidationRules::for('inventory_transaction', 'store');
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Inventory Transaction).
     */
    protected static function generateNextCode(string $prefix, string $table, string $column): string
    {
        $last = \DB::table($table)->where($column, 'like', "$prefix-%")->orderByDesc($column)->value($column);
        if ($last) {
            $num = intval(substr($last, strlen($prefix) + 1)) + 1;
        } else {
            $num = 1;
        }
        return $prefix . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
