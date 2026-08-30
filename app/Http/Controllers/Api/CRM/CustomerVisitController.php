<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerVisitController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Visit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Visit" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerVisit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerVisitController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Visit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerVisit::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->visit_date) {
            $query->whereDate('visit_date', $request->visit_date);
        }
        if ($request->visit_status) {
            $query->where('visit_status', $request->visit_status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('customer', function ($q2) use ($s) {
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
     * إنشاء سجل جديد لـ (Customer Visit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_visit', 'store'));

        return response()->json(CustomerVisit::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Visit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerVisit $customer_visit)
    {
        return $customer_visit->load([
            'route',
            'employee',
            'customer',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Visit) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerVisit $customer_visit)
    {
        $data = $request->validate(ValidationRules::for('customer_visit', 'update', $customer_visit));

        $customer_visit->update($data);

        return response()->json($customer_visit);
    }

    /**
     * حذف سجل من (Customer Visit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerVisit $customer_visit)
    {
        $customer_visit->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Visit) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerVisit::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Visit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerVisit::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Visit).
     */
    public function schema()
    {
        return ValidationRules::for('customer_visit', 'store');
    }
}
