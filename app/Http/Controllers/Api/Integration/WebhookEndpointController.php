<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class WebhookEndpointController extends Controller
{
    public function index(Request $request)
    {
        $query = WebhookEndpoint::query()->with('subscriptions');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('webhook_name', 'like', "%{$s}%")
                    ->orWhere('target_url', 'like', "%{$s}%");
            });
        }
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('webhook_endpoint', 'create'));
        return response()->json(WebhookEndpoint::create($data), 201);
    }

    public function show($id) { return WebhookEndpoint::with('subscriptions')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = WebhookEndpoint::findOrFail($id);
        $data = $request->validate(ValidationRules::for('webhook_endpoint', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { WebhookEndpoint::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
