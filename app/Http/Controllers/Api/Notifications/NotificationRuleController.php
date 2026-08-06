<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationRule;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationRule::with(['event', 'template']);

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('notification_event_id')) $query->where('notification_event_id', $request->notification_event_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_rule', 'create'));
        return response()->json(NotificationRule::create($data), 201);
    }

    public function show($id)
    {
        return NotificationRule::with(['event', 'template'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_rule', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationRule::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationRule::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationRule::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
