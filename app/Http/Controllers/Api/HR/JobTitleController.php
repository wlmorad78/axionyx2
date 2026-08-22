<?php
/**
 * =====================================================================
 * متحكم (Controller): JobTitleController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Job Title
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Job Title" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JobTitleController extends Controller
{
    /**
     * عرض قائمة سجلات (Job Title) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = JobTitle::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->job_family_id) $query->where('job_family_id', $request->job_family_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Job Title) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('job_title', 'store'));
        return response()->json(JobTitle::create($data), 201);
    }

    public function show(JobTitle $jobTitle) { return $jobTitle->load('jobFamily'); }

    public function update(Request $request, JobTitle $jobTitle)
    {
        $data = $request->validate(ValidationRules::for('job_title', 'update', $jobTitle));
        $jobTitle->update($data);
        return response()->json($jobTitle);
    }

    public function destroy(JobTitle $jobTitle) { $jobTitle->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $j = JobTitle::onlyTrashed()->findOrFail($id); $j->restore(); return response()->json($j); }
    public function forceDelete(int $id) { JobTitle::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function nextCode(Request $request)
    {
        $last = JobTitle::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/JT-(\d+)/', $last->code, $m)) ? intval($m[1]) + 1 : 1;
        return response()->json(['code' => 'JT-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema() { return ValidationRules::for('job_title', 'store'); }
}
