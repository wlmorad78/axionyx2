<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxGroupDetailController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Group Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Group Detail" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxGroupDetail;
use Illuminate\Http\Request;

class TaxGroupDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Group Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TaxGroupDetail::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('tax_group_id', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Group Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tax_group_id' => 'required',
            'tax_type_id' => 'required',
            'calculation_order' => 'integer',
        ]);

        return response()->json(TaxGroupDetail::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Group Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxGroupDetail $taxGroupDetail)
    {
        return $taxGroupDetail;
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Group Detail) بناءً على المعرّف.
     */
    public function update(Request $request, TaxGroupDetail $taxGroupDetail)
    {
        $data = $request->validate([
            'tax_group_id' => 'required',
            'tax_type_id' => 'required',
            'calculation_order' => 'integer',
        ]);

        $taxGroupDetail->update($data);

        return response()->json($taxGroupDetail);
    }

    /**
     * حذف سجل من (Tax Group Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxGroupDetail $taxGroupDetail)
    {
        $taxGroupDetail->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Tax Group Detail) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $taxGroupDetail = TaxGroupDetail::onlyTrashed()->findOrFail($id);
        $taxGroupDetail->restore();

        return response()->json($taxGroupDetail);
    }

    /**
     * حذف نهائي للسجل من (Tax Group Detail) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        TaxGroupDetail::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }
}
