<?php
/**
 * =====================================================================
 * متحكم (Controller): ShelfShareItemController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Shelf Share Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Shelf Share Item" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\ShelfShareItem;
use Illuminate\Http\Request;

class ShelfShareItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Shelf Share Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ShelfShareItem::with($with);

        if ($request->shelf_share_survey_id) {
            $query->where('shelf_share_survey_id', $request->shelf_share_survey_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('brand_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Shelf Share Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'shelf_share_survey_id' => 'required|exists:shelf_share_surveys,id',
            'brand_name' => 'required|string|max:255',
            'facings_count' => 'required|integer|min:0',
            'shelf_percentage' => 'required|numeric|min:0|max:100',
        ]);

        return response()->json(ShelfShareItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Shelf Share Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ShelfShareItem $shelfShareItem)
    {
        return $shelfShareItem->load(['survey']);
    }

    /**
     * تحديث بيانات سجل موجود من (Shelf Share Item) بناءً على المعرّف.
     */
    public function update(Request $request, ShelfShareItem $shelfShareItem)
    {
        $data = $request->validate([
            'shelf_share_survey_id' => 'sometimes|required|exists:shelf_share_surveys,id',
            'brand_name' => 'sometimes|required|string|max:255',
            'facings_count' => 'sometimes|required|integer|min:0',
            'shelf_percentage' => 'sometimes|required|numeric|min:0|max:100',
        ]);

        $shelfShareItem->update($data);
        return response()->json($shelfShareItem);
    }

    /**
     * حذف سجل من (Shelf Share Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ShelfShareItem $shelfShareItem)
    {
        $shelfShareItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Shelf Share Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = ShelfShareItem::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Shelf Share Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ShelfShareItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
