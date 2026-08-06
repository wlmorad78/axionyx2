<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationAccount;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IntegrationAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = IntegrationAccount::query()->with('provider');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('account_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('integration_provider_id')) $query->where('integration_provider_id', $request->integration_provider_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('integration_account', 'create'));
        return response()->json(IntegrationAccount::create($data), 201);
    }

    public function show($id) { return IntegrationAccount::with('provider')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = IntegrationAccount::findOrFail($id);
        $data = $request->validate(ValidationRules::for('integration_account', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { IntegrationAccount::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
