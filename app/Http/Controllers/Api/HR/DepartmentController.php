<?php
/**
 * =====================================================================
 * متحكم (Controller): DepartmentController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Department
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Department" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Department) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Department::with($with);
        if ($request->company_id) {
            $query->where(function ($q) use ($request) {
                $q->where('company_id', $request->company_id)
                  ->orWhereNull('company_id');
            });
        }
        if ($request->parent_id) $query->where('parent_id', $request->parent_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderBy('sort_order')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Department) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('department', 'store'));
        if (empty($data['company_id'])) {
            $data['company_id'] = \App\Services\CompanyContext::id();
        }
        return response()->json(Department::create($data), 201);
    }

    public function show(Department $department) { return $department->load('children', 'manager'); }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate(ValidationRules::for('department', 'update', $department));
        $department->update($data);
        return response()->json($department);
    }

    public function destroy(Department $department) { $department->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $d = Department::onlyTrashed()->findOrFail($id); $d->restore(); return response()->json($d); }
    public function forceDelete(int $id) { Department::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function nextCode(Request $request)
    {
        $last = Department::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/DEPT-(\d+)/', $last->code, $m)) ? intval($m[1]) + 1 : 1;
        return response()->json(['code' => 'DEPT-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema() { return ValidationRules::for('department', 'store'); }
}
