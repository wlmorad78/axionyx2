<?php
/**
 * =====================================================================
 * متحكم (Controller): WebhookLogController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Webhook Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Webhook Log" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Webhook Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WebhookLog::query()->with('endpoint');
        if ($request->filled('webhook_endpoint_id')) $query->where('webhook_endpoint_id', $request->webhook_endpoint_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return WebhookLog::with('endpoint')->findOrFail($id); }
}
