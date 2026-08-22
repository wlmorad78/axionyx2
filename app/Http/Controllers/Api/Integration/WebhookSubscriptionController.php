<?php
/**
 * =====================================================================
 * متحكم (Controller): WebhookSubscriptionController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Webhook Subscription
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Webhook Subscription" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\WebhookSubscription;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WebhookSubscriptionController extends Controller
{
    /**
     * عرض قائمة سجلات (Webhook Subscription) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = WebhookSubscription::query()->with('endpoint', 'event');
        if ($request->filled('webhook_endpoint_id')) $query->where('webhook_endpoint_id', $request->webhook_endpoint_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Webhook Subscription) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('webhook_subscription', 'create'));
        return response()->json(WebhookSubscription::create($data), 201);
    }

    public function show($id) { return WebhookSubscription::with('endpoint', 'event')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = WebhookSubscription::findOrFail($id);
        $data = $request->validate(ValidationRules::for('webhook_subscription', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { WebhookSubscription::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
