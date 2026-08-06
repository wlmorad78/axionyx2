<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = LeaveType::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('leave_type', 'store'));

        return response()->json(LeaveType::create($data), 201);
    }

    public function show(LeaveType $leaveType)
    {
        return $leaveType;
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $data = $request->validate(ValidationRules::for('leave_type', 'update', $leaveType));

        $leaveType->update($data);

        return response()->json($leaveType);
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();

        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $leaveType = LeaveType::onlyTrashed()->findOrFail($id);
        $leaveType->restore();

        return response()->json($leaveType);
    }

    public function forceDelete(int $id)
    {
        LeaveType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('leave_type', 'store');
    }
}
