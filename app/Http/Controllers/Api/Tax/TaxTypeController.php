<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxTypeController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Type" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxType;
use Illuminate\Http\Request;

class TaxTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TaxType::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('tax_code', 'like', "%$s%")
                    ->orWhere('tax_name', 'like', "%$s%")
                    ->orWhere('tax_category', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tax_code' => 'required',
            'tax_name' => 'required',
            'tax_category' => 'required|in:VAT,WITHHOLDING,EXCISE,STAMP,OTHER',
        ]);

        return response()->json(TaxType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxType $taxType)
    {
        return $taxType;
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Type) بناءً على المعرّف.
     */
    public function update(Request $request, TaxType $taxType)
    {
        $data = $request->validate([
            'tax_code' => 'required',
            'tax_name' => 'required',
            'tax_category' => 'required|in:VAT,WITHHOLDING,EXCISE,STAMP,OTHER',
        ]);

        $taxType->update($data);

        return response()->json($taxType);
    }

    /**
     * حذف سجل من (Tax Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxType $taxType)
    {
        $taxType->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Tax Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $taxType = TaxType::onlyTrashed()->findOrFail($id);
        $taxType->restore();

        return response()->json($taxType);
    }

    /**
     * حذف نهائي للسجل من (Tax Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        TaxType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }
}
