<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingVisitDetailController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Visit Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Visit Detail" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingVisitDetail;
use Illuminate\Http\Request;

class MerchandisingVisitDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Visit Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingVisitDetail::with(['visit', 'checklist']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('score', 'like', "%{$s}%");
            });
        }

        if ($request->filled('merchandising_visit_id')) $query->where('merchandising_visit_id', $request->merchandising_visit_id);
        if ($request->filled('checklist_id')) $query->where('checklist_id', $request->checklist_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Visit Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'merchandising_visit_id' => 'required|exists:merchandising_visits,id',
            'checklist_id' => 'required|exists:merchandising_checklists,id',
            'score' => 'numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $detail = MerchandisingVisitDetail::create($data);
        return response()->json($detail, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Visit Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MerchandisingVisitDetail::with(['visit', 'checklist'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Visit Detail) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $detail = MerchandisingVisitDetail::findOrFail($id);

        $data = $request->validate([
            'merchandising_visit_id' => 'sometimes|required|exists:merchandising_visits,id',
            'checklist_id' => 'sometimes|required|exists:merchandising_checklists,id',
            'score' => 'numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $detail->update($data);
        return $detail;
    }

    /**
     * حذف سجل من (Merchandising Visit Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $detail = MerchandisingVisitDetail::findOrFail($id);
        $detail->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Visit Detail) وإعادته للعمل.
     */
    public function restore($id)
    {
        $detail = MerchandisingVisitDetail::withTrashed()->findOrFail($id);
        $detail->restore();
        return $detail;
    }

    /**
     * حذف نهائي للسجل من (Merchandising Visit Detail) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $detail = MerchandisingVisitDetail::withTrashed()->findOrFail($id);
        $detail->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
