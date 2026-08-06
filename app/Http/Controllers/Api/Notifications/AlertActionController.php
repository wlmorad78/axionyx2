<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\AlertAction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AlertActionController extends Controller
{
    public function index(Request $request)
    {
        $query = AlertAction::with(['alert', 'actionBy']);

        if ($request->filled('alert_id')) $query->where('alert_id', $request->alert_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('alert_action', 'create'));
        return response()->json(AlertAction::create($data), 201);
    }

    public function show($id)
    {
        return AlertAction::with(['alert', 'actionBy'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = AlertAction::findOrFail($id);
        $data = $request->validate(ValidationRules::for('alert_action', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        AlertAction::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = AlertAction::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        AlertAction::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
