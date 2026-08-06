<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecipient;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationRecipientController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationRecipient::with(['user']);

        if ($request->filled('notification_id')) $query->where('notification_id', $request->notification_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_recipient', 'create'));
        return response()->json(NotificationRecipient::create($data), 201);
    }

    public function show($id)
    {
        return NotificationRecipient::with(['user'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationRecipient::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_recipient', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationRecipient::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationRecipient::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationRecipient::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
