<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesmanAssignmentController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Salesman Assignment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Salesman Assignment" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesmanAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesmanAssignmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Salesman Assignment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $defaultWith = ['employee.user', 'salesTerritory', 'warehouse', 'treasury'];
        $mergedWith = array_unique(array_merge($defaultWith, $with));
        $query = SalesmanAssignment::with($mergedWith);

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->sales_territory_id) {
            $query->where('sales_territory_id', $request->sales_territory_id);
        }
        if ($request->job_role) {
            $query->where('job_role', $request->job_role);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('employee', function ($q2) use ($s) {
                    $q2->where('name', 'like', "%$s%");
                });
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Salesman Assignment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('salesman_assignment', 'store'));

        $assignment = SalesmanAssignment::create($data);

        return response()->json($assignment->load(['employee.user', 'salesTerritory', 'warehouse', 'treasury']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Salesman Assignment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesmanAssignment $salesman_assignment)
    {
        return $salesman_assignment->load([
            'employee.user',
            'salesTerritory',
            'warehouse',
            'treasury',
            'parentAssignment',
            'children.employee.user',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Salesman Assignment) بناءً على المعرّف.
     */
    public function update(Request $request, SalesmanAssignment $salesman_assignment)
    {
        $data = $request->validate(ValidationRules::for('salesman_assignment', 'update', $salesman_assignment));

        $salesman_assignment->update($data);

        return response()->json($salesman_assignment->load(['employee.user', 'salesTerritory', 'warehouse', 'treasury']));
    }

    /**
     * حذف سجل من (Salesman Assignment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesmanAssignment $salesman_assignment)
    {
        $salesman_assignment->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Salesman Assignment) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = SalesmanAssignment::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Salesman Assignment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesmanAssignment::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Salesman Assignment).
     */
    public function schema()
    {
        return ValidationRules::for('salesman_assignment', 'store');
    }
}
