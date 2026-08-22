<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingStandardItemController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Standard Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Standard Item" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingStandardItem;
use Illuminate\Http\Request;

class MerchandisingStandardItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Standard Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingStandardItem::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('merchandising_standard_id')) {
            $query->where('merchandising_standard_id', $request->merchandising_standard_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Standard Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_standard_id' => 'required',
            'item_no' => 'required|integer',
            'item_name' => 'required',
            'score' => 'nullable|integer',
            'display_order' => 'nullable|integer',
        ]);

        $item = MerchandisingStandardItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Standard Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = MerchandisingStandardItem::findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Standard Item) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = MerchandisingStandardItem::findOrFail($id);

        $validated = $request->validate([
            'merchandising_standard_id' => 'required',
            'item_no' => 'required|integer',
            'item_name' => 'required',
            'score' => 'nullable|integer',
            'display_order' => 'nullable|integer',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Merchandising Standard Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = MerchandisingStandardItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Standard Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = MerchandisingStandardItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item);
    }

    /**
     * حذف نهائي للسجل من (Merchandising Standard Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = MerchandisingStandardItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
