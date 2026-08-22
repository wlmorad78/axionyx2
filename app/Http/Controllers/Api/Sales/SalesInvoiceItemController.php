<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesInvoiceItemController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Invoice Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Invoice Item" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesInvoiceItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesInvoiceItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Invoice Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesInvoiceItem::with($with);
        if ($request->sales_invoice_id) $query->where('sales_invoice_id', $request->sales_invoice_id);
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Invoice Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_item', 'store'));
        return response()->json(SalesInvoiceItem::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Invoice Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesInvoiceItem $salesInvoiceItem)
    {
        return $salesInvoiceItem->load(['item', 'unit', 'warehouse']);
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Invoice Item) بناءً على المعرّف.
     */
    public function update(Request $request, SalesInvoiceItem $salesInvoiceItem)
    {
        $data = $request->validate(ValidationRules::for('sales_invoice_item', 'update', $salesInvoiceItem));
        $salesInvoiceItem->update($data);
        return response()->json($salesInvoiceItem);
    }

    /**
     * حذف سجل من (Sales Invoice Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesInvoiceItem $salesInvoiceItem)
    {
        $salesInvoiceItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Invoice Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = SalesInvoiceItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Sales Invoice Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesInvoiceItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Invoice Item).
     */
    public function schema()
    {
        return ValidationRules::for('sales_invoice_item', 'store');
    }
}
