<?php
/**
 * =====================================================================
 * متحكم (Controller): ItemUnitController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Item Unit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Item Unit" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ItemUnit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemUnitController extends Controller
{
    /**
     * عرض قائمة سجلات (Item Unit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = ItemUnit::with($with);

        if ($request->item_id) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Item Unit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('item_unit', 'store'));

        return response()->json(ItemUnit::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Item Unit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ItemUnit $item_unit)
    {
        return $item_unit->load(['item', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Item Unit) بناءً على المعرّف.
     */
    public function update(Request $request, ItemUnit $item_unit)
    {
        $data = $request->validate(ValidationRules::for('item_unit', 'update', $item_unit));

        $item_unit->update($data);

        return response()->json($item_unit);
    }

    /**
     * حذف سجل من (Item Unit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ItemUnit $item_unit)
    {
        $item_unit->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Item Unit) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = ItemUnit::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Item Unit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ItemUnit::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Item Unit).
     */
    public function schema()
    {
        return ValidationRules::for('item_unit', 'store');
    }
}
