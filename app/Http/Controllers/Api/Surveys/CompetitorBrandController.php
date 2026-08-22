<?php
/**
 * =====================================================================
 * متحكم (Controller): CompetitorBrandController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Competitor Brand
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Competitor Brand" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorBrand;
use Illuminate\Http\Request;

class CompetitorBrandController extends Controller
{
    /**
     * عرض قائمة سجلات (Competitor Brand) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CompetitorBrand::with($with);

        if ($request->competitor_id) {
            $query->where('competitor_id', $request->competitor_id);
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
     * إنشاء سجل جديد لـ (Competitor Brand) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'competitor_id' => 'required|exists:competitors,id',
            'brand_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        return response()->json(CompetitorBrand::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Competitor Brand) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CompetitorBrand $competitorBrand)
    {
        return $competitorBrand->load(['competitor', 'products']);
    }

    /**
     * تحديث بيانات سجل موجود من (Competitor Brand) بناءً على المعرّف.
     */
    public function update(Request $request, CompetitorBrand $competitorBrand)
    {
        $data = $request->validate([
            'competitor_id' => 'sometimes|required|exists:competitors,id',
            'brand_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $competitorBrand->update($data);
        return response()->json($competitorBrand);
    }

    /**
     * حذف سجل من (Competitor Brand) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CompetitorBrand $competitorBrand)
    {
        $competitorBrand->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Competitor Brand) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CompetitorBrand::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Competitor Brand) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CompetitorBrand::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
