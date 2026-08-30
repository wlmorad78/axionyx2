<?php
/**
 * =====================================================================
 * متحكم (Controller): InventoryRevaluationController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Inventory Revaluation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Inventory Revaluation" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryRevaluation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryRevaluationController extends Controller
{
    /**
     * عرض قائمة سجلات (Inventory Revaluation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = InventoryRevaluation::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('revaluation_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Inventory Revaluation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_revaluation', 'store'));
        if (empty($data['revaluation_no'])) {
            $data['revaluation_no'] = self::generateNextCode('IR', 'inventory_revaluations', 'revaluation_no');
        }
        return response()->json(InventoryRevaluation::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Inventory Revaluation) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(InventoryRevaluation $inventoryRevaluation)
    {
        return $inventoryRevaluation->load([
            'company', 'branch', 'warehouse',
            'items.item', 'items.unit', 'items.batch',
            'createdByEmployee', 'approvedByEmployee',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Inventory Revaluation) بناءً على المعرّف.
     */
    public function update(Request $request, InventoryRevaluation $inventoryRevaluation)
    {
        $data = $request->validate(ValidationRules::for('inventory_revaluation', 'update', $inventoryRevaluation));
        $inventoryRevaluation->update($data);
        return response()->json($inventoryRevaluation);
    }

    /**
     * حذف سجل من (Inventory Revaluation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(InventoryRevaluation $inventoryRevaluation)
    {
        $inventoryRevaluation->delete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Inventory Revaluation).
     */
    public function nextCode()
    {
        return response()->json(['code' => self::generateNextCode('IR', 'inventory_revaluations', 'revaluation_no')]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Inventory Revaluation) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = InventoryRevaluation::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Inventory Revaluation) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        InventoryRevaluation::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Inventory Revaluation).
     */
    public function schema()
    {
        return ValidationRules::for('inventory_revaluation', 'store');
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Inventory Revaluation).
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
