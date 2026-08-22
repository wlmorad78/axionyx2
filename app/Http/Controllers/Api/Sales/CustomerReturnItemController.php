<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerReturnItemController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Customer Return Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Return Item" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerReturnItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerReturnItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Return Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerReturnItem::with($with);

        if ($request->customer_return_id) {
            $query->where('customer_return_id', $request->customer_return_id);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Return Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_return_item', 'store'));
        return response()->json(CustomerReturnItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Return Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerReturnItem $customerReturnItem)
    {
        return $customerReturnItem->load(['item', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Return Item) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerReturnItem $customerReturnItem)
    {
        $data = $request->validate(ValidationRules::for('customer_return_item', 'update', $customerReturnItem));
        $customerReturnItem->update($data);
        return response()->json($customerReturnItem);
    }

    /**
     * حذف سجل من (Customer Return Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerReturnItem $customerReturnItem)
    {
        $customerReturnItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Return Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerReturnItem::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Return Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerReturnItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Return Item).
     */
    public function schema()
    {
        return ValidationRules::for('customer_return_item', 'store');
    }
}
