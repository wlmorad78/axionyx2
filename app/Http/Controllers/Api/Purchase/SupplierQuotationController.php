<?php
/**
 * =====================================================================
 * متحكم (Controller): SupplierQuotationController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Supplier Quotation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Supplier Quotation" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\SupplierQuotation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierQuotationController extends Controller
{
    /**
     * عرض قائمة سجلات (Supplier Quotation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierQuotation::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->supplier_id) $query->where('supplier_id', $request->supplier_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('quotation_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Supplier Quotation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_quotation', 'store'));
        return response()->json(SupplierQuotation::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Supplier Quotation) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SupplierQuotation $supplierQuotation)
    {
        return $supplierQuotation->load([
            'company', 'branch', 'supplier', 'createdByEmployee',
            'items.item', 'items.unit',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Supplier Quotation) بناءً على المعرّف.
     */
    public function update(Request $request, SupplierQuotation $supplierQuotation)
    {
        $data = $request->validate(ValidationRules::for('supplier_quotation', 'update', $supplierQuotation));
        $supplierQuotation->update($data);
        return response()->json($supplierQuotation);
    }

    /**
     * حذف سجل من (Supplier Quotation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SupplierQuotation $supplierQuotation)
    {
        $supplierQuotation->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Supplier Quotation) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = SupplierQuotation::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Supplier Quotation) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SupplierQuotation::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Supplier Quotation).
     */
    public function schema()
    {
        return ValidationRules::for('supplier_quotation', 'store');
    }
}
