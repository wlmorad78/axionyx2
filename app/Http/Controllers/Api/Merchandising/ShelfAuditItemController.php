<?php
/**
 * =====================================================================
 * متحكم (Controller): ShelfAuditItemController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Shelf Audit Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Shelf Audit Item" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\ShelfAuditItem;
use Illuminate\Http\Request;

class ShelfAuditItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Shelf Audit Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ShelfAuditItem::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('shelf_audit_id')) {
            $query->where('shelf_audit_id', $request->shelf_audit_id);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Shelf Audit Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shelf_audit_id' => 'required',
            'item_id' => 'required',
            'facings_count' => 'nullable|integer',
            'display_qty' => 'nullable|integer',
            'shelf_share_percent' => 'nullable|numeric',
        ]);

        $item = ShelfAuditItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Shelf Audit Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = ShelfAuditItem::findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Shelf Audit Item) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = ShelfAuditItem::findOrFail($id);

        $validated = $request->validate([
            'shelf_audit_id' => 'required',
            'item_id' => 'required',
            'facings_count' => 'nullable|integer',
            'display_qty' => 'nullable|integer',
            'shelf_share_percent' => 'nullable|numeric',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Shelf Audit Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = ShelfAuditItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Shelf Audit Item) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = ShelfAuditItem::onlyTrashed()->findOrFail($id);
        $item->restore();

        return response()->json($item);
    }

    /**
     * حذف نهائي للسجل من (Shelf Audit Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = ShelfAuditItem::onlyTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
