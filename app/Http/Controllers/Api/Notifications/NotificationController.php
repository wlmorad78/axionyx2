<?php
/**
 * =====================================================================
 * متحكم (Controller): NotificationController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Notification
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Notification" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Paginated notifications for current user.
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->where('company_id', $request->user()->company_id);

        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        if ($request->has('type')) {
            $query->where('notification_type_id', $request->type);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $notifications = $query->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($notifications);
    }

    /**
     * GET /api/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('company_id', $request->user()->company_id)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * PUT /api/notifications/{id}/read
     * Mark a notification as read.
     */
    public function markRead(Notification $notification)
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * PUT /api/notifications/read-all
     * Mark all notifications as read for current user.
     */
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('company_id', $request->user()->company_id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'All marked as read']);
    }

    /**
     * DELETE /api/notifications/{id}
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * GET /api/notifications/stats
     */
    public function stats(Request $request)
    {
        $userId = $request->user()->id;
        $companyId = $request->user()->company_id;
        $base = Notification::where('user_id', $userId)->where('company_id', $companyId);

        return response()->json([
            'total' => (clone $base)->count(),
            'unread' => (clone $base)->where('is_read', false)->count(),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
        ]);
    }
}
