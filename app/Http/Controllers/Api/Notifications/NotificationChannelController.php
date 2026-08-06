<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationChannelController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationChannel::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('channel_code', 'like', "%{$s}%")
                    ->orWhere('channel_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_channel', 'create'));
        return response()->json(NotificationChannel::create($data), 201);
    }

    public function show($id)
    {
        return NotificationChannel::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationChannel::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_channel', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationChannel::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationChannel::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationChannel::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
