<?php
/**
 * =====================================================================
 * متحكم (Controller): CompetitorProductController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Competitor Product
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Competitor Product" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorProduct;
use Illuminate\Http\Request;

class CompetitorProductController extends Controller
{
    /**
     * عرض قائمة سجلات (Competitor Product) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorProduct::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('product_code', 'like', "%$s%")
                    ->orWhere('product_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Competitor Product) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'competitor_id' => 'required|exists:competitors,id',
            'competitor_brand_id' => 'nullable|exists:competitor_brands,id',
            'product_code' => 'nullable|string|max:255',
            'product_name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:item_categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'barcode' => 'nullable|string|max:255',
            'package_size' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(CompetitorProduct::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Competitor Product) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CompetitorProduct $competitorProduct)
    {
        return $competitorProduct->load(['competitor', 'brand', 'category', 'unit', 'priceSurveyItems', 'promotionItems']);
    }

    /**
     * تحديث بيانات سجل موجود من (Competitor Product) بناءً على المعرّف.
     */
    public function update(Request $request, CompetitorProduct $competitorProduct)
    {
        $data = $request->validate([
            'competitor_id' => 'sometimes|required|exists:competitors,id',
            'competitor_brand_id' => 'nullable|exists:competitor_brands,id',
            'product_code' => 'nullable|string|max:255',
            'product_name' => 'sometimes|required|string|max:255',
            'category_id' => 'nullable|exists:item_categories,id',
            'unit_id' => 'nullable|exists:units,id',
            'barcode' => 'nullable|string|max:255',
            'package_size' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $competitorProduct->update($data);
        return response()->json($competitorProduct);
    }

    /**
     * حذف سجل من (Competitor Product) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CompetitorProduct $competitorProduct)
    {
        $competitorProduct->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Competitor Product) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CompetitorProduct::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Competitor Product) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CompetitorProduct::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
