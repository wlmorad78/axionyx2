<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationType::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('type_code', 'like', "%{$s}%")
                    ->orWhere('type_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_type', 'create'));
        return response()->json(NotificationType::create($data), 201);
    }

    public function show($id)
    {
        return NotificationType::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationType::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_type', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationType::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationType::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationType::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
