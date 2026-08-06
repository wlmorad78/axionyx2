<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationEventSubscription;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IntegrationEventSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = IntegrationEventSubscription::query()->with('account', 'event');
        if ($request->filled('integration_account_id')) $query->where('integration_account_id', $request->integration_account_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('integration_event_subscription', 'create'));
        return response()->json(IntegrationEventSubscription::create($data), 201);
    }

    public function show($id) { return IntegrationEventSubscription::with('account', 'event')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = IntegrationEventSubscription::findOrFail($id);
        $data = $request->validate(ValidationRules::for('integration_event_subscription', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { IntegrationEventSubscription::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
