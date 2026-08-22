<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingAuditPhotoController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Audit Photo
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Audit Photo" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingAuditPhoto;
use Illuminate\Http\Request;

class MerchandisingAuditPhotoController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Audit Photo) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingAuditPhoto::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        if ($request->filled('photo_type')) {
            $query->where('photo_type', $request->photo_type);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Audit Photo) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'photo_type' => 'required|in:STORE,SHELF,REFRIGERATOR,DISPLAY,POSM',
            'file_path' => 'required',
            'taken_at' => 'nullable|date',
        ]);

        $item = MerchandisingAuditPhoto::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Audit Photo) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = MerchandisingAuditPhoto::findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Audit Photo) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = MerchandisingAuditPhoto::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'photo_type' => 'required|in:STORE,SHELF,REFRIGERATOR,DISPLAY,POSM',
            'file_path' => 'required',
            'taken_at' => 'nullable|date',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Merchandising Audit Photo) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = MerchandisingAuditPhoto::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Audit Photo) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = MerchandisingAuditPhoto::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Merchandising Audit Photo) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = MerchandisingAuditPhoto::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
