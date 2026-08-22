<?php
/**
 * =====================================================================
 * متحكم (Controller): WarehouseTransferController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Warehouse Transfer
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Warehouse Transfer" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\WarehouseTransfer;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WarehouseTransferController extends Controller
{
    /**
     * عرض قائمة سجلات (Warehouse Transfer) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = WarehouseTransfer::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->from_warehouse_id) $query->where('from_warehouse_id', $request->from_warehouse_id);
        if ($request->to_warehouse_id) $query->where('to_warehouse_id', $request->to_warehouse_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transfer_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Warehouse Transfer) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('warehouse_transfer', 'store'));
        if (empty($data['transfer_no'])) {
            $data['transfer_no'] = self::generateNextCode('WT', 'warehouse_transfers', 'transfer_no');
        }
        return response()->json(WarehouseTransfer::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Warehouse Transfer) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(WarehouseTransfer $warehouseTransfer)
    {
        return $warehouseTransfer->load([
            'company', 'branch',
            'fromWarehouse', 'toWarehouse',
            'items.item', 'items.unit', 'items.batch',
            'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Warehouse Transfer) بناءً على المعرّف.
     */
    public function update(Request $request, WarehouseTransfer $warehouseTransfer)
    {
        $data = $request->validate(ValidationRules::for('warehouse_transfer', 'update', $warehouseTransfer));
        $warehouseTransfer->update($data);
        return response()->json($warehouseTransfer);
    }

    /**
     * حذف سجل من (Warehouse Transfer) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(WarehouseTransfer $warehouseTransfer)
    {
        $warehouseTransfer->delete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Warehouse Transfer).
     */
    public function nextCode()
    {
        return response()->json(['code' => self::generateNextCode('WT', 'warehouse_transfers', 'transfer_no')]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Warehouse Transfer) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = WarehouseTransfer::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Warehouse Transfer) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        WarehouseTransfer::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Warehouse Transfer).
     */
    public function schema()
    {
        return ValidationRules::for('warehouse_transfer', 'store');
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Warehouse Transfer).
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
