<?php
/**
 * =====================================================================
 * متحكم (Controller): EventController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Event
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Event" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\EventDefinition;
use App\Models\EventLog;
use App\Models\EventSubscription;
use App\Services\EventBus;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * GET /api/events
     * List all event definitions.
     */
    public function index(Request $request)
    {
        $query = EventDefinition::orderBy('category')->orderBy('sort_order');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('module')) {
            $query->where('source_module', $request->module);
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * GET /api/events/{code}
     * Get event definition with subscriptions.
     */
    public function show(string $code)
    {
        $event = EventDefinition::where('code', $code)
            ->with('subscriptions')
            ->firstOrFail();

        return response()->json(['data' => $event]);
    }

    /**
     * POST /api/events/{code}/fire
     * Manually fire an event (for testing).
     */
    public function fire(Request $request, string $code)
    {
        $request->validate(['payload' => 'nullable|array']);

        $log = EventBus::fire(
            $code,
            $request->payload ?? [],
            $request->user()->company_id,
            $request->user()->id
        );

        return response()->json(['data' => $log]);
    }

    /**
     * GET /api/events/history
     * Event log history for current company.
     */
    public function history(Request $request)
    {
        $companyId = $request->user()->company_id;
        $limit = $request->get('per_page', 50);

        $logs = EventLog::where('company_id', $companyId)
            ->with('eventDefinition:id,code,name,name_ar')
            ->latest('fired_at')
            ->paginate($limit);

        return response()->json($logs);
    }

    /**
     * GET /api/events/{code}/subscriptions
     * Get subscriptions for an event.
     */
    public function subscriptions(string $code)
    {
        $event = EventDefinition::where('code', $code)->firstOrFail();
        $subs = EventSubscription::where('event_definition_id', $event->id)
            ->orderBy('priority', 'desc')
            ->get();

        return response()->json(['data' => $subs]);
    }

    /**
     * POST /api/events/{code}/subscribe
     * Subscribe a handler to an event.
     */
    public function subscribe(Request $request, string $code)
    {
        $validated = $request->validate([
            'module_code' => 'required|string',
            'handler_class' => 'required|string',
            'priority' => 'nullable|integer',
            'config' => 'nullable|array',
        ]);

        $sub = EventBus::subscribe(
            $code,
            $validated['module_code'],
            $validated['handler_class'],
            $validated['priority'] ?? 0,
            $validated['config'] ?? []
        );

        return response()->json(['data' => $sub], 201);
    }

    /**
     * DELETE /api/events/{code}/unsubscribe
     */
    public function unsubscribe(Request $request, string $code)
    {
        $request->validate([
            'module_code' => 'required|string',
            'handler_class' => 'required|string',
        ]);

        EventBus::unsubscribe($code, $request->module_code, $request->handler_class);

        return response()->json(['message' => 'Unsubscribed']);
    }

    /**
     * GET /api/events/stats
     */
    public function stats(Request $request)
    {
        $companyId = $request->user()->company_id;

        return response()->json([
            'total_events' => EventDefinition::count(),
            'enabled_events' => EventDefinition::where('is_enabled', true)->count(),
            'total_fired' => EventLog::where('company_id', $companyId)->count(),
            'fired_today' => EventLog::where('company_id', $companyId)->whereDate('fired_at', today())->count(),
            'total_subscriptions' => EventSubscription::where('is_enabled', true)->count(),
        ]);
    }
}
