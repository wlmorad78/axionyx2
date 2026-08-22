<?php
/**
 * =====================================================================
 * متحكم (Controller): CompetitorPriceSurveyItemController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Competitor Price Survey Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Competitor Price Survey Item" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorPriceSurveyItem;
use Illuminate\Http\Request;

class CompetitorPriceSurveyItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Competitor Price Survey Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorPriceSurveyItem::with($with);

        if ($request->competitor_price_survey_id) {
            $query->where('competitor_price_survey_id', $request->competitor_price_survey_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('stock_status', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Competitor Price Survey Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'competitor_price_survey_id' => 'required|exists:competitor_price_surveys,id',
            'competitor_product_id' => 'required|exists:competitor_products,id',
            'price' => 'required|numeric|min:0',
            'promotion_price' => 'nullable|numeric|min:0',
            'stock_status' => 'required|in:AVAILABLE,LOW_STOCK,OUT_OF_STOCK',
            'notes' => 'nullable|string',
        ]);

        return response()->json(CompetitorPriceSurveyItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Competitor Price Survey Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CompetitorPriceSurveyItem $competitorPriceSurveyItem)
    {
        return $competitorPriceSurveyItem->load(['survey', 'competitorProduct']);
    }

    /**
     * تحديث بيانات سجل موجود من (Competitor Price Survey Item) بناءً على المعرّف.
     */
    public function update(Request $request, CompetitorPriceSurveyItem $competitorPriceSurveyItem)
    {
        $data = $request->validate([
            'competitor_price_survey_id' => 'sometimes|required|exists:competitor_price_surveys,id',
            'competitor_product_id' => 'sometimes|required|exists:competitor_products,id',
            'price' => 'sometimes|required|numeric|min:0',
            'promotion_price' => 'nullable|numeric|min:0',
            'stock_status' => 'sometimes|required|in:AVAILABLE,LOW_STOCK,OUT_OF_STOCK',
            'notes' => 'nullable|string',
        ]);

        $competitorPriceSurveyItem->update($data);
        return response()->json($competitorPriceSurveyItem);
    }

    /**
     * حذف سجل من (Competitor Price Survey Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CompetitorPriceSurveyItem $competitorPriceSurveyItem)
    {
        $competitorPriceSurveyItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Competitor Price Survey Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CompetitorPriceSurveyItem::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Competitor Price Survey Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CompetitorPriceSurveyItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
