<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\ScheduledNotification;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ScheduledNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = ScheduledNotification::with(['template']);

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('scheduled_notification', 'create'));
        return response()->json(ScheduledNotification::create($data), 201);
    }

    public function show($id)
    {
        return ScheduledNotification::with(['template'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = ScheduledNotification::findOrFail($id);
        $data = $request->validate(ValidationRules::for('scheduled_notification', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        ScheduledNotification::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = ScheduledNotification::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        ScheduledNotification::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
