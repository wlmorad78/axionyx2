<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationProvider;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IntegrationProviderController extends Controller
{
    public function index(Request $request)
    {
        $query = IntegrationProvider::query()->with('accounts');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('provider_code', 'like', "%{$s}%")
                    ->orWhere('provider_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('integration_provider', 'create'));
        return response()->json(IntegrationProvider::create($data), 201);
    }

    public function show($id) { return IntegrationProvider::with('accounts')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = IntegrationProvider::findOrFail($id);
        $data = $request->validate(ValidationRules::for('integration_provider', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { IntegrationProvider::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }

    public function restore($id) { $m = IntegrationProvider::withTrashed()->findOrFail($id); $m->restore(); return $m; }

    public function forceDelete($id) { IntegrationProvider::withTrashed()->findOrFail($id)->forceDelete(); return response()->json(['message' => 'Permanently deleted']); }
}
