<?php
/**
 * =====================================================================
 * متحكم (Controller): TaxRateController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Tax Rate
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Tax Rate" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\Tax\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    /**
     * عرض قائمة سجلات (Tax Rate) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TaxRate::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('tax_type_id', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Tax Rate) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tax_type_id' => 'required|exists:tax_rates,id',
            'rate_percent' => 'required|numeric',
            'effective_from' => 'required|date',
        ]);

        return response()->json(TaxRate::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Tax Rate) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(TaxRate $taxRate)
    {
        return $taxRate;
    }

    /**
     * تحديث بيانات سجل موجود من (Tax Rate) بناءً على المعرّف.
     */
    public function update(Request $request, TaxRate $taxRate)
    {
        $data = $request->validate([
            'tax_type_id' => 'required|exists:tax_rates,id',
            'rate_percent' => 'required|numeric',
            'effective_from' => 'required|date',
        ]);

        $taxRate->update($data);

        return response()->json($taxRate);
    }

    /**
     * حذف سجل من (Tax Rate) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(TaxRate $taxRate)
    {
        $taxRate->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Tax Rate) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $taxRate = TaxRate::onlyTrashed()->findOrFail($id);
        $taxRate->restore();

        return response()->json($taxRate);
    }

    /**
     * حذف نهائي للسجل من (Tax Rate) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        TaxRate::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }
}
