<?php
/**
 * =====================================================================
 * متحكم (Controller): CompetitorPromotionController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Competitor Promotion
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Competitor Promotion" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorPromotion;
use Illuminate\Http\Request;

class CompetitorPromotionController extends Controller
{
    /**
     * عرض قائمة سجلات (Competitor Promotion) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorPromotion::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('promotion_name', 'like', "%$s%")
                    ->orWhere('status', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Competitor Promotion) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'competitor_id' => 'required|exists:competitors,id',
            'promotion_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,EXPIRED,CANCELLED',
        ]);

        return response()->json(CompetitorPromotion::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Competitor Promotion) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CompetitorPromotion $competitorPromotion)
    {
        return $competitorPromotion->load(['competitor', 'items']);
    }

    /**
     * تحديث بيانات سجل موجود من (Competitor Promotion) بناءً على المعرّف.
     */
    public function update(Request $request, CompetitorPromotion $competitorPromotion)
    {
        $data = $request->validate([
            'competitor_id' => 'sometimes|required|exists:competitors,id',
            'promotion_name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:ACTIVE,EXPIRED,CANCELLED',
        ]);

        $competitorPromotion->update($data);
        return response()->json($competitorPromotion);
    }

    /**
     * حذف سجل من (Competitor Promotion) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CompetitorPromotion $competitorPromotion)
    {
        $competitorPromotion->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Competitor Promotion) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CompetitorPromotion::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Competitor Promotion) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CompetitorPromotion::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
