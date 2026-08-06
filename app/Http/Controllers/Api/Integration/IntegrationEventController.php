<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationEvent;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IntegrationEventController extends Controller
{
    public function index(Request $request)
    {
        $query = IntegrationEvent::query();

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
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('integration_event', 'create'));
        return response()->json(IntegrationEvent::create($data), 201);
    }

    public function show($id) { return IntegrationEvent::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = IntegrationEvent::findOrFail($id);
        $data = $request->validate(ValidationRules::for('integration_event', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { IntegrationEvent::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
