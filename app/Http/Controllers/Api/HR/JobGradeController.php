<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\JobGrade;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JobGradeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = JobGrade::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderBy('grade_level')->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('job_grade', 'store'));
        return response()->json(JobGrade::create($data), 201);
    }

    public function show(JobGrade $jobGrade) { return $jobGrade; }

    public function update(Request $request, JobGrade $jobGrade)
    {
        $data = $request->validate(ValidationRules::for('job_grade', 'update', $jobGrade));
        $jobGrade->update($data);
        return response()->json($jobGrade);
    }

    public function destroy(JobGrade $jobGrade) { $jobGrade->delete(); return response()->json(null, 204); }
    public function restore(int $id) { $j = JobGrade::onlyTrashed()->findOrFail($id); $j->restore(); return response()->json($j); }
    public function forceDelete(int $id) { JobGrade::onlyTrashed()->findOrFail($id)->forceDelete(); return response()->json(null, 204); }

    public function nextCode(Request $request)
    {
        $last = JobGrade::orderBy('id', 'desc')->first();
        $next = ($last && preg_match('/JG-(\d+)/', $last->code, $m)) ? intval($m[1]) + 1 : 1;
        return response()->json(['code' => 'JG-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function schema() { return ValidationRules::for('job_grade', 'store'); }
}
