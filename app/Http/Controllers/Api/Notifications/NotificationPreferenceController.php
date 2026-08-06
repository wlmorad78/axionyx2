<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationPreference::with(['notificationType', 'channel']);

        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('notification_type_id')) $query->where('notification_type_id', $request->notification_type_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_preference', 'create'));
        return response()->json(NotificationPreference::create($data), 201);
    }

    public function show($id)
    {
        return NotificationPreference::with(['notificationType', 'channel'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationPreference::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_preference', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationPreference::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationPreference::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationPreference::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
