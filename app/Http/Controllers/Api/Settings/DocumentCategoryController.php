<?php
/**
 * =====================================================================
 * متحكم (Controller): DocumentCategoryController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): Document Category
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Document Category" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\DocumentCategory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DocumentCategoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Document Category) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DocumentCategory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Document Category) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('document_category', 'create'));
        $documentCategory = DocumentCategory::create($data);
        return response()->json($documentCategory, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Document Category) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return DocumentCategory::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Document Category) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $documentCategory = DocumentCategory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('document_category', 'update', $documentCategory));
        $documentCategory->update($data);
        return $documentCategory;
    }

    /**
     * حذف سجل من (Document Category) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $documentCategory = DocumentCategory::findOrFail($id);
        $documentCategory->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Document Category) وإعادته للعمل.
     */
    public function restore($id)
    {
        $documentCategory = DocumentCategory::withTrashed()->findOrFail($id);
        $documentCategory->restore();
        return $documentCategory;
    }

    /**
     * حذف نهائي للسجل من (Document Category) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $documentCategory = DocumentCategory::withTrashed()->findOrFail($id);
        $documentCategory->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
