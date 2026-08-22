<?php
/**
 * =====================================================================
 * متحكم (Controller): CompetitorShelfItemController
 * الوحدة (Module): الاستبيانات والاستطلاعات (Surveys)
 * المورد (Resource): Competitor Shelf Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Competitor Shelf Item" ضمن وحدة "الاستبيانات والاستطلاعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Surveys;

use App\Http\Controllers\Controller;
use App\Models\CompetitorShelfItem;
use Illuminate\Http\Request;

class CompetitorShelfItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Competitor Shelf Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = CompetitorShelfItem::with('competitorProduct');

        if ($request->filled('shelf_audit_id')) {
            $query->where('shelf_audit_id', $request->shelf_audit_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Competitor Shelf Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shelf_audit_id' => 'required',
            'competitor_product_id' => 'required',
            'facings_count' => 'integer',
            'shelf_share_percent' => 'numeric',
        ]);

        $item = CompetitorShelfItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Competitor Shelf Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = CompetitorShelfItem::with('competitorProduct')->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Competitor Shelf Item) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = CompetitorShelfItem::findOrFail($id);

        $validated = $request->validate([
            'shelf_audit_id' => 'required',
            'competitor_product_id' => 'required',
            'facings_count' => 'integer',
            'shelf_share_percent' => 'numeric',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Competitor Shelf Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = CompetitorShelfItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Competitor Shelf Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = CompetitorShelfItem::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Competitor Shelf Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = CompetitorShelfItem::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
