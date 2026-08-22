<?php
/**
 * =====================================================================
 * متحكم (Controller): IntegrationJobRunController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Integration Job Run
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Integration Job Run" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationJobRun;
use Illuminate\Http\Request;

class IntegrationJobRunController extends Controller
{
    /**
     * عرض قائمة سجلات (Integration Job Run) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = IntegrationJobRun::query()->with('job');
        if ($request->filled('integration_job_id')) $query->where('integration_job_id', $request->integration_job_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return IntegrationJobRun::with('job')->findOrFail($id); }
}
