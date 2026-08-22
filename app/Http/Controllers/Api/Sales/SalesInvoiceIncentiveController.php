<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesInvoiceIncentiveController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Invoice Incentive
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Invoice Incentive" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceIncentive;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesInvoiceIncentiveController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Invoice Incentive) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesInvoiceIncentive::with($with);
        if ($request->sales_invoice_id) $query->where('sales_invoice_id', $request->sales_invoice_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Invoice Incentive) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_incentive', 'store'));
        return response()->json(SalesInvoiceIncentive::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Invoice Incentive) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesInvoiceIncentive $salesInvoiceIncentive)
    {
        return $salesInvoiceIncentive->load(['salesIncentive']);
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Invoice Incentive) بناءً على المعرّف.
     */
    public function update(Request $request, SalesInvoiceIncentive $salesInvoiceIncentive)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_incentive', 'update', $salesInvoiceIncentive));
        $salesInvoiceIncentive->update($data);
        return response()->json($salesInvoiceIncentive);
    }

    /**
     * حذف سجل من (Sales Invoice Incentive) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesInvoiceIncentive $salesInvoiceIncentive)
    {
        $salesInvoiceIncentive->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Invoice Incentive) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = SalesInvoiceIncentive::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Sales Invoice Incentive) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesInvoiceIncentive::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Invoice Incentive).
     */
    public function schema()
    {
        return ValidationRules::for('sales_invoice_incentive', 'store');
    }
}
