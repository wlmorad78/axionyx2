<?php
/**
 * =====================================================================
 * متحكم (Controller): ExternalDocumentLogController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): External Document Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "External Document Log" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\ExternalDocumentLog;
use Illuminate\Http\Request;

class ExternalDocumentLogController extends Controller
{
    /**
     * عرض قائمة سجلات (External Document Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ExternalDocumentLog::query()->with('document');
        if ($request->filled('external_document_id')) $query->where('external_document_id', $request->external_document_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return ExternalDocumentLog::with('document')->findOrFail($id); }
}
