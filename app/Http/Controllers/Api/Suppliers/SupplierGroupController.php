<?php
/**
 * =====================================================================
 * متحكم (Controller): SupplierGroupController
 * الوحدة (Module): الموردين (Suppliers)
 * المورد (Resource): Supplier Group
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Supplier Group" ضمن وحدة "الموردين".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\SupplierGroup;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SupplierGroupController extends Controller
{
    /**
     * عرض قائمة سجلات (Supplier Group) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SupplierGroup::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Supplier Group) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('supplier_group', 'store'));
        return response()->json(SupplierGroup::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Supplier Group) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SupplierGroup $supplierGroup)
    {
        return $supplierGroup->load(['company', 'suppliers']);
    }

    /**
     * تحديث بيانات سجل موجود من (Supplier Group) بناءً على المعرّف.
     */
    public function update(Request $request, SupplierGroup $supplierGroup)
    {
        $data = $request->validate(ValidationRules::for('supplier_group', 'update', $supplierGroup));
        $supplierGroup->update($data);
        return response()->json($supplierGroup);
    }

    /**
     * حذف سجل من (Supplier Group) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SupplierGroup $supplierGroup)
    {
        $supplierGroup->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Supplier Group) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = SupplierGroup::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Supplier Group) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SupplierGroup::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Supplier Group).
     */
    public function schema()
    {
        return ValidationRules::for('supplier_group', 'store');
    }
}
