<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingStandardController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Standard
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Standard" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingStandard;
use Illuminate\Http\Request;

class MerchandisingStandardController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Standard) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingStandard::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('standard_code')) {
            $query->where('standard_code', $request->standard_code);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $standards = $query->paginate($request->get('per_page', 15));

        return response()->json($standards);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Standard) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'standard_code' => 'required|string|max:50|unique:merchandising_standards,standard_code',
            'standard_name' => 'required',
            'description' => 'nullable',
            'max_score' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $standard = MerchandisingStandard::create($validated);

        return response()->json($standard, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Standard) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $standard = MerchandisingStandard::findOrFail($id);

        return response()->json($standard);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Standard) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $standard = MerchandisingStandard::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required',
            'standard_code' => 'required|string|max:50|unique:merchandising_standards,standard_code,' . $id,
            'standard_name' => 'required',
            'description' => 'nullable',
            'max_score' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $standard->update($validated);

        return response()->json($standard);
    }

    /**
     * حذف سجل من (Merchandising Standard) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $standard = MerchandisingStandard::findOrFail($id);
        $standard->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Standard) وإعادته للعمل.
     */
    public function restore($id)
    {
        $standard = MerchandisingStandard::onlyTrashed()->findOrFail($id);
        $standard->restore();

        return response()->json($standard);
    }

    /**
     * حذف نهائي للسجل من (Merchandising Standard) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $standard = MerchandisingStandard::onlyTrashed()->findOrFail($id);
        $standard->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
