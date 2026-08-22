<?php
/**
 * =====================================================================
 * متحكم (Controller): ItemPriceController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Item Price
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Item Price" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Pricing\ItemPrice;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemPriceController extends Controller
{
    /**
     * عرض قائمة سجلات (Item Price) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = ItemPrice::with($with);

        if ($request->item_id) {
            $query->where('item_id', $request->item_id);
        }

        if ($request->price_list_id) {
            $query->where('price_list_id', $request->price_list_id);
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
     * إنشاء سجل جديد لـ (Item Price) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('item_price', 'store'));

        return response()->json(ItemPrice::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Item Price) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ItemPrice $item_price)
    {
        return $item_price->load(['item', 'priceList', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Item Price) بناءً على المعرّف.
     */
    public function update(Request $request, ItemPrice $item_price)
    {
        $data = $request->validate(ValidationRules::for('item_price', 'update', $item_price));

        $item_price->update($data);

        return response()->json($item_price);
    }

    /**
     * حذف سجل من (Item Price) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ItemPrice $item_price)
    {
        $item_price->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Item Price) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = ItemPrice::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Item Price) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ItemPrice::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Item Price).
     */
    public function schema()
    {
        return ValidationRules::for('item_price', 'store');
    }
}
