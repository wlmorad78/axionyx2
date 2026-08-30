<?php
/**
 * =====================================================================
 * متحكم (Controller): CompetitorPhotoController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Competitor Photo
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Competitor Photo" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorPhoto;
use Illuminate\Http\Request;

class CompetitorPhotoController extends Controller
{
    /**
     * عرض قائمة سجلات (Competitor Photo) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorPhoto::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('photo_type', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Competitor Photo) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'nullable|exists:users,id',
            'competitor_id' => 'nullable|exists:competitors,id',
            'photo_type' => 'required|in:PRICE_TAG,SHELF,DISPLAY,PROMOTION,NEW_PRODUCT',
            'file_path' => 'required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        return response()->json(CompetitorPhoto::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Competitor Photo) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CompetitorPhoto $competitorPhoto)
    {
        return $competitorPhoto->load(['customer', 'salesRep', 'competitor']);
    }

    /**
     * تحديث بيانات سجل موجود من (Competitor Photo) بناءً على المعرّف.
     */
    public function update(Request $request, CompetitorPhoto $competitorPhoto)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'nullable|exists:users,id',
            'competitor_id' => 'nullable|exists:competitors,id',
            'photo_type' => 'sometimes|required|in:PRICE_TAG,SHELF,DISPLAY,PROMOTION,NEW_PRODUCT',
            'file_path' => 'sometimes|required|string|max:255',
            'taken_at' => 'nullable|date',
        ]);

        $competitorPhoto->update($data);
        return response()->json($competitorPhoto);
    }

    /**
     * حذف سجل من (Competitor Photo) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CompetitorPhoto $competitorPhoto)
    {
        $competitorPhoto->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Competitor Photo) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CompetitorPhoto::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Competitor Photo) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CompetitorPhoto::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
