<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\JobFamily;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JobFamilyController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = JobFamily::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('job_family', 'store'));
        return response()->json(JobFamily::create($data), 201);
    }

    public function show(JobFamily $jobFamily) { return $jobFamily; }

    public function update(Request $request, JobFamily $jobFamily)
    {
        $data = $request->validate(ValidationRules::for('job_family', 'update', $jobFamily));
        $jobFamily->update($data);
        return response()->json($jobFamily);
    }

    public function destroy(JobFamily $jobFamily) { $jobFamily->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $j = JobFamily::onlyTrashed()->findOrFail($id); $j->restore(); return response()->json($j); }
    public function forceDelete(int $id) { JobFamily::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function nextCode(Request $request)
    {
        $last = JobFamily::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/JF-(\d+)/', $last->code, $m)) ? intval($m[1]) + 1 : 1;
        return response()->json(['code' => 'JF-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema() { return ValidationRules::for('job_family', 'store'); }
}
