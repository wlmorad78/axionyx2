<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryCountDetailController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Count Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Count Detail" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryCountDetail;
use Illuminate\Http\Request;

class TreasuryCountDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Count Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : ['count'];
        $query = TreasuryCountDetail::with($with);

        if ($request->trashed) {
            $query->onlyTrashed();
        }
        if ($request->treasury_count_id) {
            $query->where('treasury_count_id', $request->treasury_count_id);
        }

        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Count Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'treasury_count_id' => 'required',
            'denomination' => 'required',
            'qty' => 'required|integer',
            'total_amount' => 'nullable|numeric',
        ]);

        $detail = TreasuryCountDetail::create($data);
        return response()->json($detail, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Count Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $detail = TreasuryCountDetail::with(['count'])->findOrFail($id);
        return response()->json($detail);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Count Detail) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $detail = TreasuryCountDetail::findOrFail($id);

        $data = $request->validate([
            'treasury_count_id' => 'required',
            'denomination' => 'required',
            'qty' => 'required|integer',
            'total_amount' => 'nullable|numeric',
        ]);

        $detail->update($data);
        return response()->json($detail);
    }

    /**
     * حذف سجل من (Treasury Count Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $detail = TreasuryCountDetail::findOrFail($id);
        $detail->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Count Detail) وإعادته للعمل.
     */
    public function restore($id)
    {
        $detail = TreasuryCountDetail::onlyTrashed()->findOrFail($id);
        $detail->restore();
        return response()->json($detail);
    }

    /**
     * حذف نهائي للسجل من (Treasury Count Detail) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $detail = TreasuryCountDetail::onlyTrashed()->findOrFail($id);
        $detail->forceDelete();
        return response()->json(null, 204);
    }
}
