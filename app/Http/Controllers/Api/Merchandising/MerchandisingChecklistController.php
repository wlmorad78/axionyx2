<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingChecklistController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Checklist
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Checklist" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingChecklist;
use Illuminate\Http\Request;

class MerchandisingChecklistController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Checklist) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingChecklist::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('check_code', 'like', "%{$s}%")
                    ->orWhere('check_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Checklist) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'check_code' => 'required|string|max:255|unique:merchandising_checklists,check_code',
            'check_name' => 'required|string|max:255',
            'max_score' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $checklist = MerchandisingChecklist::create($data);
        return response()->json($checklist, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Checklist) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MerchandisingChecklist::with('visitDetails')->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Checklist) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $checklist = MerchandisingChecklist::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'check_code' => 'sometimes|required|string|max:255|unique:merchandising_checklists,check_code,' . $id,
            'check_name' => 'sometimes|required|string|max:255',
            'max_score' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $checklist->update($data);
        return $checklist;
    }

    /**
     * حذف سجل من (Merchandising Checklist) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $checklist = MerchandisingChecklist::findOrFail($id);
        $checklist->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Checklist) وإعادته للعمل.
     */
    public function restore($id)
    {
        $checklist = MerchandisingChecklist::withTrashed()->findOrFail($id);
        $checklist->restore();
        return $checklist;
    }

    /**
     * حذف نهائي للسجل من (Merchandising Checklist) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $checklist = MerchandisingChecklist::withTrashed()->findOrFail($id);
        $checklist->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
