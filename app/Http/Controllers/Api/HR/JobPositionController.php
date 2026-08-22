<?php
/**
 * =====================================================================
 * متحكم (Controller): JobPositionController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Job Position
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Job Position" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\JobPosition;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JobPositionController extends Controller
{
    /**
     * عرض قائمة سجلات (Job Position) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = JobPosition::with($with);
        if ($request->department_id) $query->where('department_id', $request->department_id);
        if ($request->organization_unit_id) $query->where('organization_unit_id', $request->organization_unit_id);
        if ($request->job_title_id) $query->where('job_title_id', $request->job_title_id);
        if ($request->position_level_id) $query->where('position_level_id', $request->position_level_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderBy('sort_order')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Job Position) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('job_position', 'store'));
        return response()->json(JobPosition::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Job Position) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(JobPosition $jobPosition)
    {
        return $jobPosition->load(
            'department', 'organizationUnit', 'positionLevel',
            'jobTitle', 'jobGrade', 'salaryScale', 'reportsTo', 'children'
        );
    }

    /**
     * تحديث بيانات سجل موجود من (Job Position) بناءً على المعرّف.
     */
    public function update(Request $request, JobPosition $jobPosition)
    {
        $data = $request->validate(ValidationRules::for('job_position', 'update', $jobPosition));
        $jobPosition->update($data);
        return response()->json($jobPosition);
    }

    public function destroy(JobPosition $jobPosition) { $jobPosition->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $j = JobPosition::onlyTrashed()->findOrFail($id); $j->restore(); return response()->json($j); }
    public function forceDelete(int $id) { JobPosition::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function nextCode(Request $request)
    {
        $last = JobPosition::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/JP-(\d+)/', $last->code, $m)) ? intval($m[1]) + 1 : 1;
        return response()->json(['code' => 'JP-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema() { return ValidationRules::for('job_position', 'store'); }
}
