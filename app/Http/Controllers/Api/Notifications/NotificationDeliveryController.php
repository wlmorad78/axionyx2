<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class NotificationDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationDelivery::with(['channel']);

        if ($request->filled('notification_id')) $query->where('notification_id', $request->notification_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('notification_delivery', 'create'));
        return response()->json(NotificationDelivery::create($data), 201);
    }

    public function show($id)
    {
        return NotificationDelivery::with(['channel'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = NotificationDelivery::findOrFail($id);
        $data = $request->validate(ValidationRules::for('notification_delivery', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        NotificationDelivery::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = NotificationDelivery::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        NotificationDelivery::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
