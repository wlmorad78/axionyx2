<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationGroup;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationGroup::with(['members']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('group_code', 'like', "%{$s}%")
                    ->orWhere('group_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_group', 'create'));
        return response()->json(NotificationGroup::create($data), 201);
    }

    public function show($id)
    {
        return NotificationGroup::with(['members'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationGroup::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_group', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationGroup::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationGroup::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationGroup::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
