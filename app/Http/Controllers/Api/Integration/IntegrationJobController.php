<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationJob;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IntegrationJobController extends Controller
{
    public function index(Request $request)
    {
        $query = IntegrationJob::query()->with('account', 'runs');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('job_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('integration_account_id')) $query->where('integration_account_id', $request->integration_account_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('integration_job', 'create'));
        return response()->json(IntegrationJob::create($data), 201);
    }

    public function show($id) { return IntegrationJob::with('account', 'runs')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = IntegrationJob::findOrFail($id);
        $data = $request->validate(ValidationRules::for('integration_job', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { IntegrationJob::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
