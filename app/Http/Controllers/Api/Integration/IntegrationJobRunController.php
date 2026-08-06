<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationJobRun;
use Illuminate\Http\Request;

class IntegrationJobRunController extends Controller
{
    public function index(Request $request)
    {
        $query = IntegrationJobRun::query()->with('job');
        if ($request->filled('integration_job_id')) $query->where('integration_job_id', $request->integration_job_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return IntegrationJobRun::with('job')->findOrFail($id); }
}
