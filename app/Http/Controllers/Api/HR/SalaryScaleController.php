<?php
/**
 * =====================================================================
 * متحكم (Controller): SalaryScaleController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Salary Scale
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Salary Scale" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\SalaryScale;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalaryScaleController extends Controller
{
    /**
     * عرض قائمة سجلات (Salary Scale) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalaryScale::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->job_grade_id) $query->where('job_grade_id', $request->job_grade_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Salary Scale) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('salary_scale', 'store'));
        return response()->json(SalaryScale::create($data), 201);
    }

    public function show(SalaryScale $salaryScale) { return $salaryScale->load('jobGrade'); }

    public function update(Request $request, SalaryScale $salaryScale)
    {
        $data = $request->validate(ValidationRules::for('salary_scale', 'update', $salaryScale));
        $salaryScale->update($data);
        return response()->json($salaryScale);
    }

    public function destroy(SalaryScale $salaryScale) { $salaryScale->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $s = SalaryScale::onlyTrashed()->findOrFail($id); $s->restore(); return response()->json($s); }
    public function forceDelete(int $id) { SalaryScale::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function nextCode(Request $request)
    {
        $last = SalaryScale::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/SS-(\d+)/', $last->code, $m)) ? intval($m[1]) + 1 : 1;
        return response()->json(['code' => 'SS-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema() { return ValidationRules::for('salary_scale', 'store'); }
}
