<?php
/**
 * =====================================================================
 * متحكم (Controller): DocumentController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): Document
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Document" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Document;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * عرض قائمة سجلات (Document) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Document::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('document_name', 'like', "%{$s}%")
                    ->orWhere('file_path', 'like', "%{$s}%")
                    ->orWhere('reference_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Document) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('document', 'create'));
        $document = Document::create($data);
        return response()->json($document, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Document) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Document::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Document) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $data = $request->validate(ValidationRules::for('document', 'update', $document));
        $document->update($data);
        return $document;
    }

    /**
     * حذف سجل من (Document) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        $document->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Document) وإعادته للعمل.
     */
    public function restore($id)
    {
        $document = Document::withTrashed()->findOrFail($id);
        $document->restore();
        return $document;
    }

    /**
     * حذف نهائي للسجل من (Document) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $document = Document::withTrashed()->findOrFail($id);
        $document->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
