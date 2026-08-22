<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxGroupController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Group
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Group" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\Tax\TaxGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxGroupController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Group) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TaxGroup::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('group_code', 'like', "%$s%")
                    ->orWhere('group_name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Group) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'group_code' => 'required',
            'group_name' => 'required',
        ]);

        $data['company_id'] = Auth::user()->company_id;

        return response()->json(TaxGroup::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Group) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxGroup $taxGroup)
    {
        return $taxGroup;
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Group) بناءً على المعرّف.
     */
    public function update(Request $request, TaxGroup $taxGroup)
    {
        $data = $request->validate([
            'group_code' => 'required',
            'group_name' => 'required',
        ]);

        $taxGroup->update($data);

        return response()->json($taxGroup);
    }

    /**
     * حذف سجل من (Tax Group) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxGroup $taxGroup)
    {
        $taxGroup->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Tax Group) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $taxGroup = TaxGroup::onlyTrashed()->findOrFail($id);
        $taxGroup->restore();

        return response()->json($taxGroup);
    }

    /**
     * حذف نهائي للسجل من (Tax Group) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        TaxGroup::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }
}
