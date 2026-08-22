<?php
/**
 * =====================================================================
 * متحكم (Controller): IntegrationJobController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Integration Job
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Integration Job" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationJob;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IntegrationJobController extends Controller
{
    /**
     * عرض قائمة سجلات (Integration Job) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = IntegrationJob::query()->with('account', 'runs');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('job_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('integration_account_id')) $query->where('integration_account_id', $request->integration_account_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Integration Job) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('integration_job', 'create'));
        return response()->json(IntegrationJob::create($data), 201);
    }

    public function show($id) { return IntegrationJob::with('account', 'runs')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = IntegrationJob::findOrFail($id);
        $data = $request->validate(ValidationRules::for('integration_job', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { IntegrationJob::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
