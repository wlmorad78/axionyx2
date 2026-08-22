<?php
/**
 * =====================================================================
 * متحكم (Controller): IntegrationEndpointController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Integration Endpoint
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Integration Endpoint" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationEndpoint;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IntegrationEndpointController extends Controller
{
    /**
     * عرض قائمة سجلات (Integration Endpoint) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = IntegrationEndpoint::query()->with('provider');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('endpoint_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('integration_provider_id')) $query->where('integration_provider_id', $request->integration_provider_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Integration Endpoint) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('integration_endpoint', 'create'));
        return response()->json(IntegrationEndpoint::create($data), 201);
    }

    public function show($id) { return IntegrationEndpoint::with('provider')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = IntegrationEndpoint::findOrFail($id);
        $data = $request->validate(ValidationRules::for('integration_endpoint', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { IntegrationEndpoint::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
