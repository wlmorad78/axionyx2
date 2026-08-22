<?php
/**
 * =====================================================================
 * متحكم (Controller): EmployeeController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Employee
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Employee" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\User;
use App\Services\CompanyContext;
use App\Support\ValidationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * عرض قائمة سجلات (Employee) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Employee::with($with);

        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->employee_status_id) $query->where('employee_status_id', $request->employee_status_id);
        if ($request->gender) $query->where('gender', $request->gender);
        if ($request->has('roles')) {
            $roles = $request->input('roles');
            if (is_string($roles)) $roles = explode(',', $roles);
            $query->whereHas('user', function ($q) use ($roles) {
                $q->whereHas('roles', function ($rq) use ($roles) {
                    $rq->whereIn('name', $roles);
                });
            });
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name_ar', 'like', "%$s%")->orWhere('last_name_ar', 'like', "%$s%")
                  ->orWhere('employee_code', 'like', "%$s%")->orWhere('national_id', 'like', "%$s%")
                  ->orWhere('mobile', 'like', "%$s%")->orWhere('email', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Employee) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('employee', 'store'));

        if (empty($data['company_id'])) {
            $data['company_id'] = CompanyContext::id();
        }

        $employee = Employee::create($data);

        if ($request->filled('user_code') && $request->filled('user_email')) {
            $userName = trim(($data['first_name_ar'] ?? '') . ' ' . ($data['last_name_ar'] ?? ''));
            $user = User::create([
                'name' => $userName ?: $request->user_code,
                'email' => $request->user_email,
                'password' => Hash::make('123456'),
                'usercode' => $request->user_code,
                'company_id' => $data['company_id'] ?? null,
            ]);
            $roleName = $request->input('user_role', 'sales_rep');
            $user->assignRole($roleName);
            $employee->update(['user_id' => $user->id]);
        }

        return response()->json($employee, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Employee) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Employee $employee)
    {
        return $employee->load(['company', 'user', 'country', 'governorate', 'city', 'area', 'status']);
    }

    /**
     * تحديث بيانات سجل موجود من (Employee) بناءً على المعرّف.
     */
    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate(ValidationRules::for('employee', 'update', $employee));
        $employee->update($data);
        return response()->json($employee);
    }

    public function destroy(Employee $employee) { $employee->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $e = Employee::onlyTrashed()->findOrFail($id); $e->restore(); return response()->json($e); }
    public function forceDelete(int $id) { Employee::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function nextCode(Request $request)
    {
        $last = Employee::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/EMP-(\d+)/', $last->employee_code, $m)) ? intval($m[1]) + 1 : 1;
        return response()->json(['code' => 'EMP-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema() { return ValidationRules::for('employee', 'store'); }
}
