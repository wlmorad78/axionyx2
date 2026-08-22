<?php
/**
 * =====================================================================
 * متحكم (Controller): InventoryOpeningBalanceController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Inventory Opening Balance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Inventory Opening Balance" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryOpeningBalance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class InventoryOpeningBalanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Inventory Opening Balance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['warehouse', 'item', 'unit', 'branch'];
        $query = InventoryOpeningBalance::with($with);
        $companyId = $request->company_id ?? $request->header('X-Company-Id') ?? $request->user()?->company_id;
        if ($companyId) $query->where('company_id', $companyId);
        if ($request->branch_id) $query->where(function ($q) use ($request) {
            $q->where('branch_id', $request->branch_id)->orWhereNull('branch_id');
        });
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('item', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 50);
    }

    /**
     * إنشاء سجل جديد لـ (Inventory Opening Balance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('inventory_opening_balance', 'store'));
        if (isset($data['balance_date']) && !isset($data['opening_date'])) {
            $data['opening_date'] = $data['balance_date'];
        }
        if (isset($data['unit_price']) && !isset($data['unit_cost'])) {
            $data['unit_cost'] = $data['unit_price'];
        }
        if (isset($data['total_price']) && !isset($data['total_cost'])) {
            $data['total_cost'] = $data['total_price'];
        }
        if (!isset($data['company_id'])) {
            $data['company_id'] = $request->user()->company_id ?? auth()->user()->company_id;
        }
        $bodyBranchId = $request->json('branch_id');
        if ($bodyBranchId) {
            $data['branch_id'] = $bodyBranchId;
        } elseif (!isset($data['branch_id']) && $request->branch_id) {
            $data['branch_id'] = $request->branch_id;
        }
        if (!isset($data['created_by'])) {
            $data['created_by'] = $request->user()->id ?? auth()->id();
        }
        return response()->json(InventoryOpeningBalance::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Inventory Opening Balance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(InventoryOpeningBalance $inventoryOpeningBalance)
    {
        return $inventoryOpeningBalance->load([
            'company', 'branch', 'warehouse', 'item', 'unit', 'createdBy',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Inventory Opening Balance) بناءً على المعرّف.
     */
    public function update(Request $request, InventoryOpeningBalance $inventoryOpeningBalance)
    {
        $data = $request->validate(ValidationRules::for('inventory_opening_balance', 'update', $inventoryOpeningBalance));
        if (isset($data['balance_date']) && !isset($data['opening_date'])) {
            $data['opening_date'] = $data['balance_date'];
        }
        if (isset($data['unit_price']) && !isset($data['unit_cost'])) {
            $data['unit_cost'] = $data['unit_price'];
        }
        if (isset($data['total_price']) && !isset($data['total_cost'])) {
            $data['total_cost'] = $data['total_price'];
        }
        $bodyBranchId = $request->json('branch_id');
        if ($bodyBranchId) {
            $data['branch_id'] = $bodyBranchId;
        }
        $inventoryOpeningBalance->update($data);
        return response()->json($inventoryOpeningBalance);
    }

    /**
     * حذف سجل من (Inventory Opening Balance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(InventoryOpeningBalance $inventoryOpeningBalance)
    {
        $inventoryOpeningBalance->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Inventory Opening Balance) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = InventoryOpeningBalance::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Inventory Opening Balance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        InventoryOpeningBalance::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Inventory Opening Balance).
     */
    public function schema()
    {
        return ValidationRules::for('inventory_opening_balance', 'store');
    }
}
