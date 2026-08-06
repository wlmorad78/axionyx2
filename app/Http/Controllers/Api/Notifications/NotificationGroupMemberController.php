<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationGroupMember;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationGroupMemberController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationGroupMember::with(['user']);

        if ($request->filled('notification_group_id')) $query->where('notification_group_id', $request->notification_group_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_group_member', 'create'));
        return response()->json(NotificationGroupMember::create($data), 201);
    }

    public function show($id)
    {
        return NotificationGroupMember::with(['user'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationGroupMember::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_group_member', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationGroupMember::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationGroupMember::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationGroupMember::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
