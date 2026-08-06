<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationRuleRecipient;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationRuleRecipientController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationRuleRecipient::with(['notificationRule']);

        if ($request->filled('notification_rule_id')) $query->where('notification_rule_id', $request->notification_rule_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_rule_recipient', 'create'));
        return response()->json(NotificationRuleRecipient::create($data), 201);
    }

    public function show($id)
    {
        return NotificationRuleRecipient::with(['notificationRule'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationRuleRecipient::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_rule_recipient', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationRuleRecipient::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationRuleRecipient::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationRuleRecipient::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
