<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationEvent;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationEventController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationEvent::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('event_code', 'like', "%{$s}%")
                    ->orWhere('event_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('entity_type')) $query->where('entity_type', $request->entity_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_event', 'create'));
        return response()->json(NotificationEvent::create($data), 201);
    }

    public function show($id)
    {
        return NotificationEvent::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationEvent::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_event', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationEvent::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationEvent::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationEvent::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
