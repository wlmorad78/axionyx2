<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationQueue;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationQueueController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationQueue::with(['notification', 'channel']);

        if ($request->filled('notification_id')) $query->where('notification_id', $request->notification_id);
        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_queue', 'create'));
        $notificationQueue = NotificationQueue::create($data);
        return response()->json($notificationQueue, 201);
    }

    public function show($id)
    {
        return NotificationQueue::with(['notification', 'channel'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $notificationQueue = NotificationQueue::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_queue', 'update', $notificationQueue));
        $notificationQueue->update($data);
        return $notificationQueue;
    }

    public function destroy($id)
    {
        NotificationQueue::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $notificationQueue = NotificationQueue::withTrashed()->findOrFail($id);
        $notificationQueue->restore();
        return $notificationQueue;
    }

    public function forceDelete($id)
    {
        NotificationQueue::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
