<?php
/**
 * =====================================================================
 * متحكم (Controller): DailyRouteCustomerController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Daily Route Customer
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Daily Route Customer" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\DailyRouteCustomer;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DailyRouteCustomerController extends Controller
{
    /**
     * عرض قائمة سجلات (Daily Route Customer) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['dailyRoute', 'customer'];
        $query = DailyRouteCustomer::with($with);

        if ($request->daily_route_id) {
            $query->where('daily_route_id', $request->daily_route_id);
        }
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->visit_status) {
            $query->where('visit_status', $request->visit_status);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderBy('visit_order')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Daily Route Customer) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('daily_route_customer', 'store'));

        return response()->json(DailyRouteCustomer::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Daily Route Customer) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(DailyRouteCustomer $daily_route_customer)
    {
        return $daily_route_customer->load(['dailyRoute', 'customer']);
    }

    /**
     * تحديث بيانات سجل موجود من (Daily Route Customer) بناءً على المعرّف.
     */
    public function update(Request $request, DailyRouteCustomer $daily_route_customer)
    {
        $data = $request->validate(ValidationRules::for('daily_route_customer', 'update', $daily_route_customer));

        $daily_route_customer->update($data);

        return response()->json($daily_route_customer);
    }

    /**
     * حذف سجل من (Daily Route Customer) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(DailyRouteCustomer $daily_route_customer)
    {
        $daily_route_customer->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Daily Route Customer) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = DailyRouteCustomer::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Daily Route Customer) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        DailyRouteCustomer::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Daily Route Customer).
     */
    public function schema()
    {
        return ValidationRules::for('daily_route_customer', 'store');
    }
}
