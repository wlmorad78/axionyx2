<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesInvoiceTaxController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Invoice Tax
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Invoice Tax" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceTax;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesInvoiceTaxController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Invoice Tax) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesInvoiceTax::with($with);
        if ($request->sales_invoice_id) $query->where('sales_invoice_id', $request->sales_invoice_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Invoice Tax) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_tax', 'store'));
        return response()->json(SalesInvoiceTax::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Invoice Tax) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesInvoiceTax $salesInvoiceTax)
    {
        return $salesInvoiceTax;
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Invoice Tax) بناءً على المعرّف.
     */
    public function update(Request $request, SalesInvoiceTax $salesInvoiceTax)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_tax', 'update', $salesInvoiceTax));
        $salesInvoiceTax->update($data);
        return response()->json($salesInvoiceTax);
    }

    /**
     * حذف سجل من (Sales Invoice Tax) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesInvoiceTax $salesInvoiceTax)
    {
        $salesInvoiceTax->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Invoice Tax) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = SalesInvoiceTax::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Sales Invoice Tax) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesInvoiceTax::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Invoice Tax).
     */
    public function schema()
    {
        return ValidationRules::for('sales_invoice_tax', 'store');
    }
}
