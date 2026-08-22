<?php
/**
 * =====================================================================
 * متحكم (Controller): SupplierQuotationItemController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Supplier Quotation Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Supplier Quotation Item" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\SupplierQuotationItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierQuotationItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Supplier Quotation Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierQuotationItem::with($with);
        if ($request->supplier_quotation_id) $query->where('supplier_quotation_id', $request->supplier_quotation_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('item', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Supplier Quotation Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_quotation_item', 'store'));
        $item = SupplierQuotationItem::create($data);
        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Supplier Quotation Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SupplierQuotationItem $supplierQuotationItem)
    {
        return $supplierQuotationItem->load(['supplierQuotation', 'item', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Supplier Quotation Item) بناءً على المعرّف.
     */
    public function update(Request $request, SupplierQuotationItem $supplierQuotationItem)
    {
        $data = $request->validate(ValidationRules::for('supplier_quotation_item', 'update', $supplierQuotationItem));
        $supplierQuotationItem->update($data);
        return response()->json($supplierQuotationItem);
    }

    /**
     * حذف سجل من (Supplier Quotation Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SupplierQuotationItem $supplierQuotationItem)
    {
        $supplierQuotationItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Supplier Quotation Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = SupplierQuotationItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Supplier Quotation Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SupplierQuotationItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Supplier Quotation Item).
     */
    public function schema()
    {
        return ValidationRules::for('supplier_quotation_item', 'store');
    }
}
