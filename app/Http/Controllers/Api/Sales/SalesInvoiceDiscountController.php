<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesInvoiceDiscountController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Invoice Discount
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Invoice Discount" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceDiscount;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesInvoiceDiscountController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Invoice Discount) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesInvoiceDiscount::with($with);
        if ($request->sales_invoice_id) $query->where('sales_invoice_id', $request->sales_invoice_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Invoice Discount) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_discount', 'store'));
        return response()->json(SalesInvoiceDiscount::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Invoice Discount) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesInvoiceDiscount $salesInvoiceDiscount)
    {
        return $salesInvoiceDiscount;
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Invoice Discount) بناءً على المعرّف.
     */
    public function update(Request $request, SalesInvoiceDiscount $salesInvoiceDiscount)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_discount', 'update', $salesInvoiceDiscount));
        $salesInvoiceDiscount->update($data);
        return response()->json($salesInvoiceDiscount);
    }

    /**
     * حذف سجل من (Sales Invoice Discount) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesInvoiceDiscount $salesInvoiceDiscount)
    {
        $salesInvoiceDiscount->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Invoice Discount) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = SalesInvoiceDiscount::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Sales Invoice Discount) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesInvoiceDiscount::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Invoice Discount).
     */
    public function schema()
    {
        return ValidationRules::for('sales_invoice_discount', 'store');
    }
}
