<?php
/**
 * =====================================================================
 * متحكم (Controller): ItemBarcodeController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Item Barcode
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Item Barcode" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemBarcode;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemBarcodeController extends Controller
{
    /**
     * عرض قائمة سجلات (Item Barcode) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = ItemBarcode::with($with);

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
     * إنشاء سجل جديد لـ (Item Barcode) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('item_barcode', 'store'));

        return response()->json(ItemBarcode::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Item Barcode) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ItemBarcode $item_barcode)
    {
        return $item_barcode->load(['item', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Item Barcode) بناءً على المعرّف.
     */
    public function update(Request $request, ItemBarcode $item_barcode)
    {
        $data = $request->validate(ValidationRules::for('item_barcode', 'update', $item_barcode));

        $item_barcode->update($data);

        return response()->json($item_barcode);
    }

    /**
     * حذف سجل من (Item Barcode) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ItemBarcode $item_barcode)
    {
        $item_barcode->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Item Barcode) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = ItemBarcode::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Item Barcode) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ItemBarcode::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Item Barcode).
     */
    public function schema()
    {
        return ValidationRules::for('item_barcode', 'store');
    }
}
