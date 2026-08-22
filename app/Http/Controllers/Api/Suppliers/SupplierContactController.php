<?php
/**
 * =====================================================================
 * متحكم (Controller): SupplierContactController
 * الوحدة (Module): الموردين (Suppliers)
 * المورد (Resource): Supplier Contact
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Supplier Contact" ضمن وحدة "الموردين".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\Suppliers\SupplierContact;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierContactController extends Controller
{
    /**
     * عرض قائمة سجلات (Supplier Contact) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierContact::with($with);

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('contact_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Supplier Contact) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_contact', 'store'));
        return response()->json(SupplierContact::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Supplier Contact) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SupplierContact $supplierContact)
    {
        return $supplierContact->load(['supplier']);
    }

    /**
     * تحديث بيانات سجل موجود من (Supplier Contact) بناءً على المعرّف.
     */
    public function update(Request $request, SupplierContact $supplierContact)
    {
        $data = $request->validate(ValidationRules::for('supplier_contact', 'update', $supplierContact));
        $supplierContact->update($data);
        return response()->json($supplierContact);
    }

    /**
     * حذف سجل من (Supplier Contact) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SupplierContact $supplierContact)
    {
        $supplierContact->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Supplier Contact) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = SupplierContact::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Supplier Contact) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SupplierContact::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Supplier Contact).
     */
    public function schema()
    {
        return ValidationRules::for('supplier_contact', 'store');
    }
}
