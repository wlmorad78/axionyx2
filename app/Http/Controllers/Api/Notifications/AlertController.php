<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notifications\Alert;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $query = Alert::with(['alertRule']);

        if ($request->filled('alert_rule_id')) $query->where('alert_rule_id', $request->alert_rule_id);
        if ($request->filled('severity')) $query->where('severity', $request->severity);
        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('alert', 'create'));
        return response()->json(Alert::create($data), 201);
    }

    public function show($id)
    {
        return Alert::with(['alertRule'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $model = Alert::findOrFail($id);
        $data = $request->validate(ValidationRules::for('alert', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id)
    {
        Alert::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $model = Alert::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    public function forceDelete($id)
    {
        Alert::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
