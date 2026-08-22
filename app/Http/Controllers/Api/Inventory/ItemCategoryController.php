<?php
/**
 * =====================================================================
 * متحكم (Controller): ItemCategoryController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Item Category
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Item Category" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ItemCategoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Item Category) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = ItemCategory::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
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
     * إنشاء سجل جديد لـ (Item Category) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('item_category', 'store'));

        if (empty($data['company_id'])) {
            $data['company_id'] = $request->header('X-Company-Id')
                ?? auth()->user()->company_id
                ?? null;
        }

        return response()->json(ItemCategory::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Item Category) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $model = ItemCategory::withoutTrashed()->findOrFail($id);
        return response()->json($model->load(['company', 'productCompany', 'subCategories']));
    }

    /**
     * تحديث بيانات سجل موجود من (Item Category) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = ItemCategory::withoutTrashed()->findOrFail($id);
        $data = $request->validate(ValidationRules::for('item_category', 'update', $model));

        $model->update($data);
        $model->refresh();

        return response()->json($model);
    }

    /**
     * حذف سجل من (Item Category) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $model = ItemCategory::withoutTrashed()->findOrFail($id);
        $model->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Item Category) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = ItemCategory::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Item Category) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ItemCategory::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Item Category).
     */
    public function nextCode(Request $request)
    {
        $query = ItemCategory::withTrashed()
            ->where('code', 'like', 'CAT-%');

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        $last = $query->get()
            ->filter(fn($item) => preg_match('/^CAT-\d{5}$/', $item->code))
            ->map(fn($item) => (int) preg_replace('/\D/', '', $item->code))
            ->filter(fn($num) => $num > 0)
            ->max();

        $next = ($last ?? 0) + 1;

        return response()->json(['code' => 'CAT-' . str_pad($next, 5, '0', STR_PAD_LEFT)]);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Item Category).
     */
    public function schema()
    {
        return ValidationRules::for('item_category', 'store');
    }
}
