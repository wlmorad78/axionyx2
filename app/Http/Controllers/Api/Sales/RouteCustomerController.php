<?php
/**
 * =====================================================================
 * متحكم (Controller): RouteCustomerController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Route Customer
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Route Customer" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\RouteCustomer;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class RouteCustomerController extends Controller
{
    /**
     * عرض قائمة سجلات (Route Customer) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = RouteCustomer::with($with);

        if ($request->route_id) {
            $query->where('route_id', $request->route_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
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
     * إنشاء سجل جديد لـ (Route Customer) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('route_customer', 'store'));
        unset($data['days'], $data['day_of_week']);

        return response()->json(RouteCustomer::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Route Customer) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(RouteCustomer $route_customer)
    {
        return $route_customer->load([
            'route',
            'customer',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Route Customer) بناءً على المعرّف.
     */
    public function update(Request $request, RouteCustomer $route_customer)
    {
        $data = $request->validate(ValidationRules::for('route_customer', 'update', $route_customer));
        unset($data['days'], $data['day_of_week']);

        $route_customer->update($data);

        return response()->json($route_customer);
    }

    /**
     * حذف سجل من (Route Customer) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(RouteCustomer $route_customer)
    {
        $route_customer->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Route Customer) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = RouteCustomer::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Route Customer) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        RouteCustomer::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Route Customer).
     */
    public function schema()
    {
        return ValidationRules::for('route_customer', 'store');
    }
}
